<?php
namespace Tests\Feature;
class SignageViewSyntaxTest extends \Tests\TestCase {
    public function test_signage_templates_compile_to_valid_php(): void {
        foreach(array_merge(glob(resource_path('views/admin/signage/*.blade.php')),glob(resource_path('views/signage/*.blade.php'))) as $file){
            try{token_get_all(app('blade.compiler')->compileString(file_get_contents($file)),TOKEN_PARSE);}
            catch(\ParseError $e){$this->fail(basename($file).': '.$e->getMessage());}
            $this->addToAssertionCount(1);
        }
    }
}
