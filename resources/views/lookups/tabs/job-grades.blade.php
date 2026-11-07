@php($isEditing = $editing !== null)

<x-card :title="($isEditing ? 'تعديل ' : 'إضافة ').$singular">
    <form method="POST"
          action="{{ $isEditing
              ? route('lookups.update', ['type' => $tab, 'lookup' => $editing->id])
              : route('lookups.store', $tab) }}">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <div class="form-grid">
            <x-form.input name="name" label="اسم الدرجة الوظيفية" :value="$editing?->name" required />
            <x-form.input name="rank_order" type="number" min="0" label="ترتيب العرض"
                          :value="$editing?->rank_order" hint="الأقل يظهر أولًا في القوائم." />
        </div>

        <div class="actions" style="margin-block-start:14px">
            <button class="btn" type="submit">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ $isEditing ? 'حفظ التعديل' : 'إضافة' }}
            </button>
            @if ($isEditing)
                <a class="btn btn-ghost" href="{{ route('lookups.index', ['tab' => $tab]) }}">إلغاء</a>
            @endif
        </div>
    </form>
</x-card>

<div style="margin-block-start:18px">
    <x-card :title="'القائمة ('.count($items).')'" :flush="true">
        @if ($items->isEmpty())
            <x-empty text="لا توجد درجات وظيفية بعد." />
        @else
            <x-table>
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>ترتيب العرض</th>
                        <th>عدد المتدربين</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td class="num">{{ $item->rank_order ?? '—' }}</td>
                            <td><x-badge :tone="$item->trainees_count > 0 ? 'brand' : 'neutral'">{{ $item->trainees_count }}</x-badge></td>
                            <td class="actions-cell">
                                <div class="actions" style="justify-content:flex-end">
                                    <a class="btn btn-ghost btn-sm"
                                       href="{{ route('lookups.index', ['tab' => $tab, 'edit' => $item->id]) }}">تعديل</a>
                                    <form method="POST"
                                          action="{{ route('lookups.destroy', ['type' => $tab, 'lookup' => $item->id]) }}"
                                          data-confirm="هل تريد حذف الدرجة «{{ $item->name }}»؟">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm" type="submit">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </x-card>
</div>
