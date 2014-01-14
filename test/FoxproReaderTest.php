<?php
/**
 * class FoxProReaderTest
 */

/** */
require '../src/foxpro-reader.php';

/**
 * test class for FoxProReaderTest
 *
 * @author     Zdenek Gebauer <zdenek.gebauer@centrum.cz>
 */
class FoxProReaderTest extends PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * test files from FoxPro 2.6 DOS
     */
    public function testFoxpro26()
    {
        $dbf = new FoxProReader(__DIR__.'/test26.dbf');

        $this->assertEquals(3, $dbf->numRecords());

        $expect = array('1', 'Adam', 'description');
        $this->assertEquals($expect, $dbf->getRecord(0));
        $expect = array('2', 'Bob', "multiline\r\ndescription");
        $this->assertEquals($expect, $dbf->getRecord(1));
        $this->assertFalse($dbf->getRecord(2));

        $expect = array('ID'=>'1', 'NAME'=>'Adam', 'DESC'=>'description');
        $this->assertEquals($expect, $dbf->getRecord(0, TRUE));
        $expect = array('ID'=>'2', 'NAME'=>'Bob', 'DESC'=>"multiline\r\ndescription");
        $this->assertEquals($expect, $dbf->getRecord(1, TRUE));
    }

    public function testInvalidExtension()
    {
        try {
            $dbf = new FoxProReader(__DIR__.'/file.ext');
        } catch (Exception $e) {
            $this->assertContains('File must have extension dbf', $e->getMessage());
        }
    }

    public function testMissingDbfFile()
    {
        try {
            $dbf = new FoxProReader(__DIR__.'/notexist.dbf');
        } catch (Exception $e) {
            $this->assertContains('No such file or directory', $e->getMessage());
        }
    }

    public function testMissingFptFile()
    {
        try {
            $dbf = new FoxProReader(__DIR__.'/testnofpt.dbf');
        } catch (Exception $e) {
            $this->assertContains('testnofpt.fpt not found', $e->getMessage());
        }
    }


}