<?php

namespace App\Services;

use App\Models\FasilitasOlahraga;
use App\Models\LayananKesehatan;
use App\Models\Tempat;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class DirektoriRegistry
{
    /**
     * Peta fitur publik API (kuliner/olahraga/kesehatan) ke kelas model.
     *
     * @var array<string, class-string<Model>>
     */
    public const MODEL = [
        'kuliner' => Tempat::class,
        'olahraga' => FasilitasOlahraga::class,
        'kesehatan' => LayananKesehatan::class,
    ];

    /**
     * Peta fitur publik API ke kunci config/osm.php.
     *
     * @var array<string, string>
     */
    public const KUNCI_FITUR = [
        'kuliner' => 'tempat',
        'olahraga' => 'olahraga',
        'kesehatan' => 'kesehatan',
    ];

    /**
     * @return class-string<Model>
     */
    public static function model(string $fitur): string
    {
        if (! isset(self::MODEL[$fitur])) {
            throw new InvalidArgumentException('Fitur direktori tidak dikenal: '.$fitur);
        }

        return self::MODEL[$fitur];
    }

    public static function kunciFitur(string $fitur): string
    {
        if (! isset(self::KUNCI_FITUR[$fitur])) {
            throw new InvalidArgumentException('Fitur direktori tidak dikenal: '.$fitur);
        }

        return self::KUNCI_FITUR[$fitur];
    }

    /**
     * @return list<string>
     */
    public static function fiturTerdaftar(): array
    {
        return array_keys(self::MODEL);
    }
}
