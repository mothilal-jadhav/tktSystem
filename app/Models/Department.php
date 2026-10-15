<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name'])]
class Department extends Model
{
    public const HR = 'HR';

    public const SALES = 'Sales';

    public const IT = 'IT';

    public const WELLNESS = 'Wellness';

    public const CLAIMS = 'Claims';

    public const ENDORSEMENT = 'Endorsement';

    /**
     * Every department, in the order they were created.
     *
     * @var list<string>
     */
    public const ALL = [
        self::HR,
        self::SALES,
        self::IT,
        self::WELLNESS,
        self::CLAIMS,
        self::ENDORSEMENT,
    ];
}
