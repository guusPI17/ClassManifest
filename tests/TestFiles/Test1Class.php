<?php

namespace Guuspi17\ClassManifest\Tests\TestFiles;

use Rsp\CacheInterface;
use Rsp\ContainerInterface;

/**
 * Тестовый класс
 * @property int $id
 * @property string|null $question
 * @property string|null $answer
 * @property int|null $sort
 * @property string $date_create [datetime]
 * @property string $date_update [datetime]
 * @property int $id_editor [bigint(20) unsigned]
 */
class Test1Class extends AbstractTest1Class
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'test1';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'sort', 'id_editor'], 'integer'],
            [['question', 'answer'], 'string'],
            [['id'], 'unique'],
            [['date_create', 'date_update'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'snowflake' => [
                'class' => 'xutl\snowflake\SnowflakeBehavior',
                'attribute' => 'id',
            ],
            'timestamp' => [
                'updatedAtAttribute' => 'date_update',
                'createdAtAttribute' => 'date_create',
            ]
        ];
    }

    public function beforeSave($insert): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'question' => 'Вопрос',
            'answer' => 'Ответ',
            'sort' => 'Сортировка',
        ];
    }

    public static function getAttributesSettingForHistoryView(): array
    {
        return [];
    }

    public function getParentIdForHistory(): string
    {
        return '';
    }

    public static function getMapNameRecordEventAndLogEvent(): array
    {
        return [];
    }

    public function isDeletedForHistory(array $oldAttributes): bool
    {
        return false;
    }

    public function triggerEvent(int $event, $user = null)
    {
        return '';
    }
}
