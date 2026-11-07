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
            <x-form.input name="name" label="اسم النوع" :value="$editing?->name" required wrapper="full" />
            <x-form.textarea name="description" label="الوصف" :value="$editing?->description" wrapper="full" />
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
            <x-empty text="لا توجد أنواع دورات بعد." />
        @else
            <x-table>
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الوصف</th>
                        <th>عدد الدورات</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td class="muted">{{ $item->description ?: '—' }}</td>
                            <td><x-badge :tone="$item->courses_count > 0 ? 'brand' : 'neutral'">{{ $item->courses_count }}</x-badge></td>
                            <td class="actions-cell">
                                <div class="actions" style="justify-content:flex-end">
                                    <a class="btn btn-ghost btn-sm"
                                       href="{{ route('lookups.index', ['tab' => $tab, 'edit' => $item->id]) }}">تعديل</a>
                                    <form method="POST"
                                          action="{{ route('lookups.destroy', ['type' => $tab, 'lookup' => $item->id]) }}"
                                          data-confirm="هل تريد حذف النوع «{{ $item->name }}»؟">
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
