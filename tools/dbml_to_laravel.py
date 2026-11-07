#!/usr/bin/env python3
"""DBML -> Laravel (MySQL) generator.
Usage: python3 dbml_to_laravel.py training_center_erd.dbml [out_dir]
Generates: database/migrations/*, app/Enums/*, app/Models/*
"""
import re, sys, os, hashlib, collections

SKIP_TABLES = {'roles', 'permissions', 'role_permissions', 'user_roles'}  # replaced by spatie/laravel-permission
RENAME_COLS = {('users', 'password_hash'): 'password'}


# ---------------------------------------------------------------- helpers
def studly(s): return ''.join(p.capitalize() for p in s.split('_'))
def camel(s):
    t = studly(s); return t[0].lower() + t[1:]
def singular(w):
    if not w.endswith('s'): return w
    if w.endswith('ies'): return w[:-3] + 'y'
    if re.search(r'(ch|sh|ss|x)es$', w): return w[:-2]
    if w.endswith('ss'): return w
    return w[:-1]
def cls(table): return studly(singular(table))

def split_top(s):
    parts, cur, q, depth = [], '', None, 0
    for ch in s:
        if q:
            cur += ch
            if ch == q: q = None
        elif ch in "'`": q = ch; cur += ch
        elif ch in '([': depth += 1; cur += ch
        elif ch in ')]': depth -= 1; cur += ch
        elif ch == ',' and depth == 0: parts.append(cur.strip()); cur = ''
        else: cur += ch
    if cur.strip(): parts.append(cur.strip())
    return parts

def strip_comments(text):
    out = []
    for line in text.splitlines():
        if line.strip().startswith('//'): continue
        res, q, i = '', None, 0
        while i < len(line):
            ch = line[i]
            if q:
                res += ch
                if ch == q: q = None
            elif ch in "'`": q = ch; res += ch
            elif line.startswith('//', i): break
            else: res += ch
            i += 1
        out.append(res.rstrip())
    return out


# ---------------------------------------------------------------- parse
def parse_table(name, body):
    t = {'name': name, 'cols': [], 'indexes': [], 'pk': []}
    in_idx = False
    for raw in body:
        s = raw.strip()
        if not s: continue
        if s.startswith('indexes') and s.endswith('{'): in_idx = True; continue
        if in_idx:
            if s == '}': in_idx = False; continue
            m = re.match(r'^\(([^)]*)\)\s*(?:\[(.*)\])?$', s)
            if m:
                cols = [c.strip() for c in m.group(1).split(',')]
                opts = [o.strip().lower() for o in split_top(m.group(2) or '')]
                if 'pk' in opts: t['pk'] = cols
                elif 'unique' in opts: t['indexes'].append(('unique', cols))
                else: t['indexes'].append(('index', cols))
            continue
        if s.startswith('Note:'): continue
        m = re.match(r'^(\w+)\s+(\w+(?:\([\d,\s]+\))?)\s*(?:\[(.*)\])?$', s)
        if not m:
            print('!! could not parse line in', name, ':', s); continue
        c = dict(name=m.group(1), type=m.group(2), pk=False, inc=False, unique=False,
                 notnull=False, default=None, ref=None)
        for opt in split_top(m.group(3) or ''):
            ol = opt.lower()
            if ol == 'pk': c['pk'] = True
            elif ol == 'increment': c['inc'] = True
            elif ol == 'unique': c['unique'] = True
            elif ol == 'not null': c['notnull'] = True
            elif ol.startswith('default:'): c['default'] = opt.split(':', 1)[1].strip()
            elif ol.startswith('ref:'):
                r = re.match(r'ref:\s*([<>\-])\s*(\w+)\.(\w+)', opt)
                c['ref'] = (r.group(1), r.group(2), r.group(3))
        c['name'] = RENAME_COLS.get((name, c['name']), c['name'])
        t['cols'].append(c)
    return t

def parse(src):
    lines = strip_comments(src)
    enums, tables = collections.OrderedDict(), collections.OrderedDict()
    i = 0
    while i < len(lines):
        l = lines[i].strip()
        m = re.match(r'^Enum\s+(\w+)\s*\{$', l)
        if m:
            vals = []; i += 1
            while lines[i].strip() != '}':
                if lines[i].strip(): vals.append(lines[i].strip().split()[0])
                i += 1
            enums[m.group(1)] = vals
        elif re.match(r'^Table\s+\w+\s*\{$', l):
            name = re.match(r'^Table\s+(\w+)', l).group(1)
            i += 1; depth = 1; body = []
            while True:
                s = lines[i].strip()
                if s.endswith('{'): depth += 1
                if s == '}':
                    depth -= 1
                    if depth == 0: break
                body.append(lines[i]); i += 1
            tables[name] = parse_table(name, body)
        elif re.match(r'^(TableGroup|Project)\s+\w+\s*\{$', l):
            while lines[i].strip() != '}': i += 1
        i += 1
    return enums, tables


# ---------------------------------------------------------------- migrations
def php_default(d):
    if d.startswith('`'): return None
    return d

def short_name(table, cols, kind):
    n = f"{table}_{'_'.join(cols)}_{kind}"
    if len(n) <= 64: return n
    return f"{table[:24]}_{hashlib.md5(n.encode()).hexdigest()[:10]}_{kind}"

def col_line(t, c, enums):
    n, ty = c['name'], c['type']
    if c['pk'] and c['inc']: return "$table->id();"
    in_pk = c['name'] in t['pk']
    if c['ref']:
        base = f"foreignId('{n}')"
    elif ty in enums:
        vals = ', '.join(f"'{v}'" for v in enums[ty]); base = f"enum('{n}', [{vals}])"
    else:
        m = re.match(r'varchar\((\d+)\)', ty); d = re.match(r'decimal\((\d+),\s*(\d+)\)', ty)
        if m: base = f"string('{n}', {m.group(1)})"
        elif d: base = f"decimal('{n}', {d.group(1)}, {d.group(2)})"
        else:
            base = {'integer': f"integer('{n}')", 'bigint': f"bigInteger('{n}')",
                    'smallint': f"smallInteger('{n}')", 'text': f"text('{n}')",
                    'boolean': f"boolean('{n}')", 'date': f"date('{n}')", 'time': f"time('{n}')",
                    'timestamp': f"dateTime('{n}')", 'jsonb': f"json('{n}')"}[ty]
    mods = ''
    dflt = c['default']
    is_now = dflt is not None and dflt.startswith('`')
    nullable = not (c['notnull'] or in_pk or (dflt is not None))
    if nullable: mods += '->nullable()'
    if is_now: mods += '->useCurrent()'
    elif dflt is not None: mods += f"->default({php_default(dflt)})"
    if c['unique']: mods += '->unique()'
    return f"$table->{base}{mods};"

def migration_create(t, enums):
    body = []
    for c in t['cols']: body.append(col_line(t, c, enums))
    if t['name'] == 'users': body.append("$table->rememberToken();")
    if t['pk']: body.append("$table->primary([" + ', '.join(f"'{x}'" for x in t['pk']) + "]);")
    has_created = any(c['name'] == 'created_at' for c in t['cols'])
    if not t['pk'] and not has_created: body.append("$table->timestamps();")
    for kind, cols in t['indexes']:
        arr = '[' + ', '.join(f"'{x}'" for x in cols) + ']'
        nm = short_name(t['name'], cols, kind)
        body.append(f"$table->{kind}({arr}, '{nm}');")
    inner = '\n'.join('            ' + b for b in body)
    return f"""<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{{
    public function up(): void
    {{
        Schema::create('{t['name']}', function (Blueprint $table) {{
{inner}
        }});
    }}

    public function down(): void
    {{
        Schema::dropIfExists('{t['name']}');
    }}
}};
"""

def migration_fks(tables):
    ups, downs = [], []
    for t in tables.values():
        if t['name'] in SKIP_TABLES: continue
        fk = [c for c in t['cols'] if c['ref']]
        if not fk: continue
        u = [f"        Schema::table('{t['name']}', function (Blueprint $table) {{"]
        d = [f"        Schema::table('{t['name']}', function (Blueprint $table) {{"]
        for c in fk:
            nm = short_name(t['name'], [c['name']], 'foreign')
            nullable = not (c['notnull'] or c['name'] in t['pk'] or c['default'] is not None)
            on = '->nullOnDelete()' if nullable else ''
            u.append(f"            $table->foreign('{c['name']}', '{nm}')->references('{c['ref'][2]}')->on('{c['ref'][1]}'){on};")
            d.append(f"            $table->dropForeign('{nm}');")
        u.append('        });'); d.append('        });')
        ups.append('\n'.join(u)); downs.append('\n'.join(d))
    return f"""<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

/**
 * All foreign keys are added last to avoid ordering problems and circular references
 * (users <-> trainers/trainees, trainee_warnings -> course_sessions, ...).
 */
return new class extends Migration
{{
    public function up(): void
    {{
{chr(10).join(ups)}
    }}

    public function down(): void
    {{
{chr(10).join(reversed(downs))}
    }}
}};
"""


# ---------------------------------------------------------------- enums & models
def enum_case(v):
    return 'SelfNominated' if v == 'self' else studly(v)

def gen_enum(name, vals):
    cname = studly(name[:-5] if name.endswith('_enum') else name)
    cases = '\n'.join(f"    case {enum_case(v)} = '{v}';" for v in vals)
    return cname, f"<?php\n\nnamespace App\\Enums;\n\nenum {cname}: string\n{{\n{cases}\n}}\n"

def is_pivot(t):
    refs = [c for c in t['cols'] if c['ref']]
    return len(t['pk']) == 2 and len(refs) == 2 and all(c['name'] in t['pk'] for c in refs)

def build_relations(tables):
    rels = collections.defaultdict(list)  # table -> [(method, kind, body)]
    fk_count = collections.Counter()
    for t in tables.values():
        if t['name'] in SKIP_TABLES or is_pivot(t): continue
        for c in t['cols']:
            if c['ref']: fk_count[(t['name'], c['ref'][1])] += 1
    for t in tables.values():
        if t['name'] in SKIP_TABLES: continue
        if is_pivot(t):
            a, b = [c for c in t['cols'] if c['ref']]
            extra = [c['name'] for c in t['cols'] if not c['ref']]
            wp = f"->withPivot({', '.join(repr(e) for e in extra)})" if extra else ''
            ta, tb = a['ref'][1], b['ref'][1]
            if ta == tb:
                fwd, inv = ('prerequisites', 'requiredFor')
            else:
                fwd, inv = camel(tb), camel(ta)
            rels[ta].append((fwd, 'BelongsToMany', f"belongsToMany({cls(tb)}::class, '{t['name']}', '{a['name']}', '{b['name']}'){wp}", cls(tb)))
            rels[tb].append((inv, 'BelongsToMany', f"belongsToMany({cls(ta)}::class, '{t['name']}', '{b['name']}', '{a['name']}'){wp}", cls(ta)))
            continue
        for c in t['cols']:
            if not c['ref']: continue
            op, r, _ = c['ref']
            n = c['name']
            bname = camel(n[:-3]) if n.endswith('_id') else camel(n)
            rels[t['name']].append((bname, 'BelongsTo', f"belongsTo({cls(r)}::class, '{n}')", cls(r)))
            if n.endswith('_by'): continue
            if r == t['name']:
                rels[r].append(('children', 'HasMany', f"hasMany({cls(t['name'])}::class, '{n}')", cls(t['name'])))
            elif op == '-':
                rels[r].append((camel(singular(t['name'])), 'HasOne', f"hasOne({cls(t['name'])}::class, '{n}')", cls(t['name'])))
            else:
                mname = camel(t['name'])
                if fk_count[(t['name'], r)] > 1:
                    mname += 'As' + studly(n[:-3] if n.endswith('_id') else n)
                rels[r].append((mname, 'HasMany', f"hasMany({cls(t['name'])}::class, '{n}')", cls(t['name'])))
    return rels

def gen_model(t, enums, rels):
    name = t['name']; C = cls(name)
    imports = set(); casts = []
    for c in t['cols']:
        ty = c['type']
        if ty in enums:
            ec = studly(ty[:-5] if ty.endswith('_enum') else ty)
            imports.add(f"App\\Enums\\{ec}"); casts.append(f"'{c['name']}' => {ec}::class,")
        elif ty == 'boolean': casts.append(f"'{c['name']}' => 'boolean',")
        elif ty == 'jsonb': casts.append(f"'{c['name']}' => 'array',")
        elif ty == 'date': casts.append(f"'{c['name']}' => 'date',")
        elif ty == 'timestamp': casts.append(f"'{c['name']}' => 'datetime',")
        else:
            d = re.match(r'decimal\(\d+,\s*(\d+)\)', ty)
            if d: casts.append(f"'{c['name']}' => 'decimal:{d.group(1)}',")
    if name == 'users': casts.append("'password' => 'hashed',")
    seen, methods = set(), []
    for (m, kind, body, _target) in rels.get(name, []):
        orig = m; k = 2
        while m in seen: m = f"{orig}{k}"; k += 1
        if m != orig: print(f"!! relation name clash in {C}: {orig} -> {m}")
        seen.add(m); imports.add(f"Illuminate\\Database\\Eloquent\\Relations\\{kind}")
        methods.append(f"    public function {m}(): {kind}\n    {{\n        return $this->{body};\n    }}")
    has_created = any(c['name'] == 'created_at' for c in t['cols'])
    extra = ''
    if has_created: extra += "\n    const UPDATED_AT = null;\n"
    if name == 'users':
        imports.update(['Illuminate\\Foundation\\Auth\\User as Authenticatable', 'Illuminate\\Notifications\\Notifiable', 'Spatie\\Permission\\Traits\\HasRoles'])
        head = "class User extends Authenticatable\n{\n    use Notifiable, HasRoles;\n"
        extra += "\n    protected $hidden = ['password', 'remember_token'];\n"
    else:
        imports.add('Illuminate\\Database\\Eloquent\\Model')
        head = f"class {C} extends Model\n{{\n"
    use_block = '\n'.join(f"use {i};" for i in sorted(imports))
    cast_block = ''
    if casts:
        cast_block = "\n    protected function casts(): array\n    {\n        return [\n" + '\n'.join('            ' + x for x in casts) + "\n        ];\n    }\n"
    meth = ('\n' + '\n\n'.join(methods) + '\n') if methods else ''
    return C, f"""<?php

namespace App\\Models;

{use_block}

{head}    protected $table = '{name}';
    protected $guarded = [];
{extra}{cast_block}{meth}}}
"""


# ---------------------------------------------------------------- main
def main():
    src = open(sys.argv[1], encoding='utf-8').read()
    out = sys.argv[2] if len(sys.argv) > 2 else '.'
    enums, tables = parse(src)
    for d in ('database/migrations', 'app/Enums', 'app/Models'):
        os.makedirs(os.path.join(out, d), exist_ok=True)
    n = 0
    for t in tables.values():
        if t['name'] in SKIP_TABLES: continue
        n += 1
        fn = f"2026_01_01_{n:06d}_create_{t['name']}_table.php"
        open(os.path.join(out, 'database/migrations', fn), 'w', encoding='utf-8').write(migration_create(t, enums))
    open(os.path.join(out, 'database/migrations', '2026_01_01_009999_add_foreign_keys.php'), 'w', encoding='utf-8').write(migration_fks(tables))
    for name, vals in enums.items():
        c, code = gen_enum(name, vals)
        open(os.path.join(out, 'app/Enums', c + '.php'), 'w', encoding='utf-8').write(code)
    rels = build_relations(tables)
    models = 0
    for t in tables.values():
        if t['name'] in SKIP_TABLES or is_pivot(t): continue
        c, code = gen_model(t, enums, rels)
        open(os.path.join(out, 'app/Models', c + '.php'), 'w', encoding='utf-8').write(code)
        models += 1
    print(f"tables: {n} | enums: {len(enums)} | models: {models} | relations: {sum(len(v) for v in rels.values())}")

if __name__ == '__main__':
    main()
