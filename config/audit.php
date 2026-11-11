<?php

/**
 * Arabic labels for audited entity types.
 */
return [
    'entities' => [
        'App\\Models\\User' => 'مستخدم',
        'App\\Models\\Branch' => 'فرع',
        'App\\Models\\Accreditation' => 'اعتماد',
        'App\\Models\\BranchAccreditation' => 'اعتماد فرع',
        'Spatie\\Permission\\Models\\Role' => 'دور',
    ],

    'actions' => [
        'user.created' => 'إضافة مستخدم',
        'user.updated' => 'تعديل مستخدم',
        'user.deleted' => 'حذف مستخدم',
        'role.created' => 'إضافة دور',
        'role.updated' => 'تعديل دور',
        'role.deleted' => 'حذف دور',
        'branch.created' => 'إضافة فرع',
        'branch.updated' => 'تعديل فرع',
        'branch.deleted' => 'حذف فرع',
        'branch_accreditation.created' => 'ربط اعتماد بفرع',
        'branch_accreditation.updated' => 'تعديل اعتماد فرع',
        'branch_accreditation.deleted' => 'إزالة اعتماد من فرع',
        'accreditation.created' => 'إضافة اعتماد',
        'accreditation.updated' => 'تعديل اعتماد',
        'accreditation.deleted' => 'حذف اعتماد',
    ],
];
