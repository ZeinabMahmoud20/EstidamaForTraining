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
            <x-form.input name="name" label="اسم التصنيف" :value="$editing?->name" required />
            <x-form.select name="parent_id" label="التصنيف الأب" :options="$categoryOptions"
                           placeholder="بدون تصنيف أب" :selected="$editing?->parent_id" />
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
            <x-empty text="لا توجد تصنيفات بعد." />
        @else
            <x-table>
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>التصنيف الأب</th>
                        <th>الدورات</th>
                        <th>التصنيفات الفرعية</th>
                        <th>المدربون</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td>{{ $item->parent?->name ?? '—' }}</td>
                            <td><x-badge :tone="$item->courses_count > 0 ? 'brand' : 'neutral'">{{ $item->courses_count }}</x-badge></td>
                            <td><x-badge :tone="$item->children_count > 0 ? 'brand' : 'neutral'">{{ $item->children_count }}</x-badge></td>
                            <td><x-badge :tone="$item->trainers_count > 0 ? 'brand' : 'neutral'">{{ $item->trainers_count }}</x-badge></td>
                            <td class="actions-cell">
                                <div class="actions" style="justify-content:flex-end">
                                    <a class="btn btn-ghost btn-sm"
                                       href="{{ route('lookups.index', ['tab' => $tab, 'edit' => $item->id]) }}">تعديل</a>
                                    <form method="POST"
                                          action="{{ route('lookups.destroy', ['type' => $tab, 'lookup' => $item->id]) }}"
                                          data-confirm="هل تريد حذف التصنيف «{{ $item->name }}»؟">
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
