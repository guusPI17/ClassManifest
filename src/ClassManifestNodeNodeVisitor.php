<?php

namespace Guuspi17\ClassManifest;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class ClassManifestNodeNodeVisitor extends NodeVisitorAbstract implements ClassManifestNodeVisitorInterface
{
    /**
     * Ключ массива - расширители
     * @var string
     */
    public const KEY_EXTENDS = 'extends';

    /**
     * Ключ массива - интерфейсы
     * @var string
     */
    public const KEY_INTERFACES = 'interfaces';

    /**
     * Ключ массива - абстрактный ли класс
     * @var string
     */
    public const KEY_IS_ABSTRACT = 'is_abstract';

    /**
     * Список классов с информацией по ним
     */
    protected array $classes = [];

    /**
     * Список интерфейсов с информацией по ним
     */
    protected array $interfaces = [];

    /**
     * @inheritDoc
     */
    public function resetState(): void
    {
        $this->classes = [];
        $this->interfaces = [];
    }

    /**
     * @inheritdoc
     */
    public function beforeTraverse(array $nodes): void
    {
        $this->resetState();
    }

    /**
     * @inheritdoc
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $extends = [];
            $interfaces = [];

            if ($node->extends) {
                $extends = [(string)$node->extends];
            }

            if ($node->implements) {
                foreach ($node->implements as $interface) {
                    $interfaces[] = (string)$interface;
                }
            }

            $this->classes[(string)$node->namespacedName] = [
                static::KEY_EXTENDS => $extends,
                static::KEY_INTERFACES => $interfaces,
                static::KEY_IS_ABSTRACT => $node->isAbstract(),
            ];
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $extends = [];
            foreach ($node->extends as $ancestor) {
                $extends[] = (string)$ancestor;
            }
            $this->interfaces[(string)$node->namespacedName] = [
                static::KEY_EXTENDS => $extends,
            ];
        }
        if (!$node instanceof Node\Stmt\Namespace_) {
            //прекратить обход, так как здесь нам нужна только информация высокого уровня

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * @inheritDoc
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }
}