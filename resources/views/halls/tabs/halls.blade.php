@php($canManage = auth()->user()?->can('HALLS_MANAGE'))

<x-card title="بحث وتصفية">
    <form method="GET" action="{{ route('halls.index') }}" class="filter-grid">
        <x-form.input name="q" label="بحث" :value="request('q')" placeholder="كود القاعة أو اسمها..." />
        <x-form.select name="branch_id" label="الفرع" :options="$branches" placeholder="كل الفروع"
                       :selected="request('branch_id')" />

        <div class="field">
            <label for="is_active">الحالة</label>
            <select id="is_active" name="is_active" class="control">
                <option value="">الكل</option>
                <option value="1" @selected(request('is_active') === '1')>نشطة</option>
                <option value="0" @selected(request('is_active') === '0')>غير نشطة</option>
            </select>
        </div>

        <div class="actions full">
            <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> تطبيق</button>
            <a class="btn btn-ghost" href="{{ route('halls.index') }}">إعادة ضبط</a>
        </div>
    </form>
</x-card>

<div style="margin-block-start:18px">
    <x-card :flush="true">
        @if ($halls->isEmpty())
            <x-empty text="لا توجد قاعات مطابقة لبحثك." />
        @else
            <x-table>
                <thead>
                    <tr>
                        <th>الكود</th>
                        <th>القاعة</th>
                        <th>الفرع</th>
                        <th>السعة</th>
                        <th>التجهيزات</th>
                        <th>أيام الجلسات</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($halls as $hall)
                        <tr>
                            <td class="num">{{ $hall->code }}</td>
                            <td>
                                <a href="{{ route('halls.show', $hall) }}"><strong>{{ $hall->name }}</strong></a>
                                @if ($hall->floor || $hall->location_description)
                                    <div class="muted" style="font-size:.8rem">
                                        {{ $hall->floor ? 'الدور: '.$hall->floor : '' }}
                                        @if ($hall->floor && $hall->location_description) · @endif
                                        {{ $hall->location_description }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $hall->branch?->name ?? '—' }}</td>
                            <td class="num">{{ $hall->capacity }}</td>
                            <td>
                                <x-badge :tone="$hall->equipment_count > 0 ? 'brand' : 'neutral'">
                                    {{ $hall->equipment_count }}
                                </x-badge>
                            </td>
                            <td>
                                <x-badge :tone="$hall->session_days_count > 0 ? 'ok' : 'neutral'">
                                    {{ $hall->session_days_count }}
                                </x-badge>
                            </td>
                            <td>
                                @if ($hall->is_active)
                                    <x-badge tone="ok">نشطة</x-badge>
                                @else
                                    <x-badge>غير نشطة</x-badge>
                                @endif
                            </td>
                            <td class="actions-cell">
                                <div class="actions" style="justify-content:flex-end">
                                    <a class="btn btn-ghost btn-sm" href="{{ route('halls.show', $hall) }}">تفاصيل</a>
                                    @if ($canManage)
                                        <a class="btn btn-outline btn-sm" href="{{ route('halls.edit', $hall) }}">تعديل</a>
                                        <form method="POST" action="{{ route('halls.destroy', $hall) }}"
                                              data-confirm="هل تريد حذف القاعة «{{ $hall->name }}»؟">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" type="submit">حذف</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>

            <div style="padding:0 18px 14px">
                {{ $halls->links() }}
            </div>
        @endif
    </x-card>
</div>
