<?php

namespace Guuspi17\ClassManifest\Tests;

use Guuspi17\ClassManifest\ClassManifest;
use Guuspi17\ClassManifest\Filters\WithoutAbstractClassFilter;
use Guuspi17\ClassManifest\Tests\TestFiles\AbstractTest1Class;
use Guuspi17\ClassManifest\Tests\TestFiles\AbstractTest2Class;
use Guuspi17\ClassManifest\Tests\TestFiles\Test0Class;
use Guuspi17\ClassManifest\Tests\TestFiles\Test0Interface;
use Guuspi17\ClassManifest\Tests\TestFiles\Test1Class;
use Guuspi17\ClassManifest\Tests\TestFiles\Test1Interface;
use Guuspi17\ClassManifest\Tests\TestFiles\Test2Class;
use Guuspi17\ClassManifest\Tests\TestFiles\Test2Interface;
use PHPUnit\Framework\TestCase;

class ClassManifestTest extends TestCase
{
    private const TEST_FILES_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'TestFiles';

    /**
     * @dataProvider getCasesTestClassManifest
     */
    public function testClassManifest(array $initData, array $expected)
    {
        $classManifest = (new ClassManifest())
            ->setDirForSearch([$initData['testFilesPath']])
        ;
        $classManifest->init();

        // проверяем, что верно находим классы и интерфейсы
        $this->assertEquals($expected['classes'], $classManifest->getClasses());
        $this->assertEquals($expected['interfaces'], $classManifest->getInterfaces());

        // проверяем, что верно находим расширителей класса
        foreach ($initData['classExtenders'] as $class => $filters) {
            $this->assertEquals(
                $expected['classExtenders'][$class],
                $classManifest->getClassExtenders($class, $filters)
            );
        }

        // проверяем, что верно находим расширителей интерфейса
        foreach ($initData['interfaceExtenders'] as $interface => $filters) {
            $this->assertEquals(
                $expected['interfaceExtenders'][$interface],
                $classManifest->getInterfaceExtenders($interface, $filters)
            );
        }

        // проверяем, что верно находим разработчиков интерфейсов
        foreach ($initData['interfaceImplementors'] as $interface => $filters) {
            $this->assertEquals(
                $expected['interfaceImplementors'][$interface],
                $classManifest->getInterfaceImplementors($interface, $filters)
            );
        }
    }

    /**
     * Кейсы для теста "testClassManifest"
     * @return iterable
     */
    public function getCasesTestClassManifest(): iterable
    {
        $classes = [
            Test0Class::class => Test0Class::class,
            Test1Class::class => Test1Class::class,
            Test2Class::class => Test2Class::class,
            AbstractTest1Class::class => AbstractTest1Class::class,
            AbstractTest2Class::class => AbstractTest2Class::class,
        ];
        $interfaces = [
            Test0Interface::class => Test0Interface::class,
            Test1Interface::class => Test1Interface::class,
            Test2Interface::class => Test2Interface::class,
        ];

        // проверяем основные комбинации доступных методов манифеста
        yield [
            'initData' => [
                'testFilesPath' => self::TEST_FILES_PATH,
                'classExtenders' => [
                    AbstractTest1Class::class => [],
                    AbstractTest2Class::class => [],
                    Test0Class::class => [],
                    Test1Class::class => [],
                    Test2Class::class => [],
                ],
                'interfaceExtenders' => [
                    Test0Interface::class => [],
                    Test1Interface::class => [],
                    Test2Interface::class => [],
                ],
                'interfaceImplementors' => [
                    Test1Interface::class => [],
                    Test2Interface::class => [],
                ],
            ],
            'expected' => [
                'classes' => $classes,
                'interfaces' => $interfaces,
                'classExtenders' => [
                    AbstractTest1Class::class => [
                        Test1Class::class => Test1Class::class,
                    ],
                    AbstractTest2Class::class => [
                        Test1Class::class => Test1Class::class,
                        Test2Class::class => Test2Class::class,
                        AbstractTest1Class::class => AbstractTest1Class::class,
                    ],
                    Test0Class::class => [],
                    Test1Class::class => [],
                    Test2Class::class => [],
                ],
                'interfaceExtenders' => [
                    Test0Interface::class => [],
                    Test1Interface::class => [],
                    Test2Interface::class => [
                        Test0Interface::class => Test0Interface::class,
                        Test1Interface::class => Test1Interface::class,
                    ],
                ],
                'interfaceImplementors' => [
                    Test0Interface::class => [
                        Test0Class::class => Test0Class::class,
                    ],
                    Test1Interface::class => [
                        Test2Class::class => Test2Class::class,
                    ],
                    Test2Interface::class => [
                        AbstractTest1Class::class => AbstractTest1Class::class,
                        Test0Class::class => Test0Class::class,
                        Test1Class::class => Test1Class::class,
                        Test2Class::class => Test2Class::class,
                    ],
                ],
            ],
        ];

        // проверяем фильтр WithoutAbstractClassFilter => true
        yield [
            'initData' => [
                'testFilesPath' => self::TEST_FILES_PATH,
                'classExtenders' => [],
                'interfaceExtenders' => [],
                'interfaceImplementors' => [
                    Test2Interface::class => [
                        WithoutAbstractClassFilter::getCode() => true,
                    ],
                ],
            ],
            'expected' => [
                'classes' => $classes,
                'interfaces' => $interfaces,
                'classExtenders' => [],
                'interfaceExtenders' => [],
                'interfaceImplementors' => [
                    Test2Interface::class => [
                        Test0Class::class => Test0Class::class,
                        Test1Class::class => Test1Class::class,
                        Test2Class::class => Test2Class::class,
                    ],
                ],
            ],
        ];
    }
}