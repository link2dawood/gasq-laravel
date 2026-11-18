<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/** Every PDF template must still compile to valid PHP. */
class PdfBladeCompilesTest extends TestCase
{
    public function test_every_pdf_blade_compiles(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views/pdf')));
        $checked = 0;
        foreach ($files as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $compiled = Blade::compileString(file_get_contents($file->getPathname()));
            $result = null;
            $output = [];
            $tmp = tempnam(sys_get_temp_dir(), 'blade').'.php';
            file_put_contents($tmp, $compiled);
            exec('php -l '.escapeshellarg($tmp).' 2>&1', $output, $result);
            @unlink($tmp);
            $this->assertSame(0, $result, $file->getPathname().' does not compile: '.implode("\n", $output));
            $checked++;
        }
        $this->assertGreaterThan(30, $checked);
    }
}
