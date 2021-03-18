<?php


namespace Plugin\migrate\process;


use Drupal\idc_migration\Plugin\migrate\process\Pairtree;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

class PairtreeTest extends MigrateProcessTestCase {
    private $underTest = NULL;

    protected function setUp() {
        parent::setUp();
        $this->underTest = new Pairtree([], 'pairtree', []);
    }

    public function testDefaults() {
        $xsum = "c9a060c39365820edc5d1a51f221d49e96a8a730";
        $result = $this->underTest->transform($xsum, $this->migrateExecutable, $this->row, '');
        $this->assertSame("c9/a0/60/c39365820edc5d1a51f221d49e96a8a730", $result);
    }

    public function testTruncate() {
        $config['truncate'] = false;
        $this->underTest = new Pairtree($config, 'pairtree', []);
        $xsum = "c9a060c39365820edc5d1a51f221d49e96a8a730";
        $result = $this->underTest->transform($xsum, $this->migrateExecutable, $this->row, '');
        $this->assertSame("c9/a0/60/c9a060c39365820edc5d1a51f221d49e96a8a730", $result);
    }

    public function testPairlen() {
        $config['pairlen'] = 3;
        $this->underTest = new Pairtree($config, 'pairtree', []);
        $xsum = "c9a060c39365820edc5d1a51f221d49e96a8a730";
        $result = $this->underTest->transform($xsum, $this->migrateExecutable, $this->row, '');
        $this->assertSame("c9a/060/c39/365820edc5d1a51f221d49e96a8a730", $result);
    }

    public function testDepth() {
        $config['depth'] = 5;
        $this->underTest = new Pairtree($config, 'pairtree', []);
        $xsum = "c9a060c39365820edc5d1a51f221d49e96a8a730";
        $result = $this->underTest->transform($xsum, $this->migrateExecutable, $this->row, '');
        $this->assertSame("c9/a0/60/c3/93/65820edc5d1a51f221d49e96a8a730", $result);
    }
}