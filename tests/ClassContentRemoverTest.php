<?php

namespace Guuspi17\ClassManifest\Tests;

use Guuspi17\ClassManifest\ClassContentRemover;
use PHPUnit\Framework\TestCase;

class ClassContentRemoverTest extends TestCase
{
    private const TEST_FILES_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'TestFiles';

    /**
     * @dataProvider getCasesTestRemoveContent
     */
    public function testRemoveContent(array $initData, array $expected)
    {
        $content = ClassContentRemover::removeContent(
            $initData['filePath'],
            $initData['cutOffDepth'],
        );
        $this->assertEquals($expected['newContent'], $content);
    }

    /**
     * Кейсы для теста "testRemoveContent"
     * @return iterable
     */
    public function getCasesTestRemoveContent(): iterable
    {
        // тестируем получение 1 уровня вложенности класса, который является наследником и реализует интерфейс
        yield [
            'initData' => [
                'cutOffDepth' => 1,
                'filePath' => self::TEST_FILES_PATH . DIRECTORY_SEPARATOR . 'Test1Class.php',
            ],
            'expected' => [
                'newContent' => '<?php
 namespace Guuspi17\ClassManifest\Tests\TestFiles; use Rsp\CacheInterface; use Rsp\ContainerInterface; class Test1Class extends AbstractTest1Class {}',
            ],
        ];

        // тестируем получение 2 уровня вложенности класса, который является наследником и реализует интерфейс
        yield [
            'initData' => [
                'cutOffDepth' => 2,
                'filePath' => self::TEST_FILES_PATH . DIRECTORY_SEPARATOR . 'Test1Class.php',
            ],
            'expected' => [
                'newContent' => '<?php
 namespace Guuspi17\ClassManifest\Tests\TestFiles; use Rsp\CacheInterface; use Rsp\ContainerInterface; class Test1Class extends AbstractTest1Class { public static function tableName() {} public function rules() {} public function behaviors(): array {} public function beforeSave($insert): bool {} public function attributeLabels() {} public static function getAttributesSettingForHistoryView(): array {} public function getParentIdForHistory(): string {} public static function getMapNameRecordEventAndLogEvent(): array {} public function isDeletedForHistory(array $oldAttributes): bool {} public function triggerEvent(int $event, $user = null) {} }',
            ],
        ];

        // тестируем получение 1 уровня вложенности абстрактного класса
        yield [
            'initData' => [
                'cutOffDepth' => 1,
                'filePath' => self::TEST_FILES_PATH . DIRECTORY_SEPARATOR . 'AbstractTest1Class.php',
            ],
            'expected' => [
                'newContent' => '<?php
 namespace Guuspi17\ClassManifest\Tests\TestFiles; abstract class AbstractTest1Class extends AbstractTest2Class implements Test2Interface {}',
            ],
        ];

        // тестируем получение 1 уровня вложенности интерфейса, который является наследником
        yield [
            'initData' => [
                'cutOffDepth' => 1,
                'filePath' => self::TEST_FILES_PATH . DIRECTORY_SEPARATOR . 'Test1Interface.php',
            ],
            'expected' => [
                'newContent' => '<?php
 namespace Guuspi17\ClassManifest\Tests\TestFiles; use BaseParser; use BaseParser\CheckList; interface Test1Interface extends Test2Interface {}',
            ],
        ];

        // тестируем получение 1 уровня вложенности интерфейса
        yield [
            'initData' => [
                'cutOffDepth' => 1,
                'filePath' => self::TEST_FILES_PATH . DIRECTORY_SEPARATOR . 'Test2Interface.php',
            ],
            'expected' => [
                'newContent' => '<?php
 namespace Guuspi17\ClassManifest\Tests\TestFiles; interface Test2Interface {}',
            ],
        ];
    }
}