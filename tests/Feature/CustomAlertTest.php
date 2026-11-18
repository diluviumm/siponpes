<?php

namespace Tests\Feature;

use App\Models\Kelas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class CustomAlertTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_no_native_browser_dialogs_remain_in_views_or_scripts(): void
    {
        $paths = [
            resource_path('views'),
            resource_path('js'),
        ];

        $offenders = [];

        foreach ($paths as $path) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

            foreach ($iterator as $file) {
                if (! $file->isFile() || ! in_array($file->getExtension(), ['php', 'js'], true)) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                if (preg_match('/\b(alert|confirm|prompt)\s*\(/', $contents)) {
                    $offenders[] = $file->getPathname();
                }
            }
        }

        $this->assertSame([], $offenders, 'Masih ada pemakaian alert/confirm/prompt bawaan browser.');
    }

    public function test_delete_forms_use_custom_confirmation_attribute(): void
    {
        $school = $this->createSchool('Sekolah Alert Custom');
        Kelas::create([
            'nama_kelas' => '7A',
            'wali_kelas' => '-',
            'kapasitas' => 10,
            'branch_id' => $school->id,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->get(route('kelas.index'))
            ->assertOk()
            ->assertSee('data-confirm', false)
            ->assertSee('confirmDialog', false);
    }
}
