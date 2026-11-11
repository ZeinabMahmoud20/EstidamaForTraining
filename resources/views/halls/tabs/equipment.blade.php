<x-card title="إضافة تجهيز">
    <form method="POST" action="{{ route('equipment.store') }}" class="filter-grid">
        @csrf
        <x-form.input name="name" label="اسم التجهيز" required placeholder="مثال: بروجيكتور..." />
        <div class="actions full">
            <button class="btn" type="submit"><i class="fa-solid fa-plus"></i> إضافة</button>
            <a class="btn btn-ghost" href="{{ route('halls.index') }}">رجوع للقاعات</a>
        </div>
    </form>
</x-card>

<div style="margin-block-start:18px">
    <x-card title="التجهيزات المسجّلة" :flush="true">
        @if ($equipment->isEmpty())
            <x-empty text="لا توجد تجهيزات مسجّلة بعد." />
        @else
            <x-table>
                <thead>
                    <tr>
                        <th>التجهيز</th>
                        <th>عدد القاعات</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($equipment as $item)
                        <tr>
                            <td>
                                <form method="POST" action="{{ route('equipment.update', $item) }}"
                                      style="display:flex;gap:8px;align-items:center">
                                    @csrf
                                    @method('PUT')
                                    <input name="name" class="control" value="{{ $item->name }}" required
                                           aria-label="اسم التجهيز">
                                    <button class="btn btn-outline btn-sm" type="submit">حفظ</button>
                                </form>
                            </td>
                            <td>
                                <x-badge :tone="$item->halls_count > 0 ? 'brand' : 'neutral'">
                                    {{ $item->halls_count }}
                                </x-badge>
                            </td>
                            <td class="actions-cell">
                                <form method="POST" action="{{ route('equipment.destroy', $item) }}"
                                      data-confirm="هل تريد حذف التجهيز «{{ $item->name }}»؟">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </x-card>
</div>
