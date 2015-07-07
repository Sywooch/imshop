<?php

namespace im\cms\models;

use im\cms\Module;

/**
 * This is the model class for table "{{%content_widgets}}".
 *
 * @property string $content
 */
class ContentWidget extends Widget
{
    const TYPE = 'content';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['content'], 'required']
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Module::t('page', 'ID'),
            'content' => Module::t('page', 'Content')
        ];
    }

    /**
     * @inheritdoc
     */
    public function getCMSTitle()
    {
        return Module::t('page', 'Content widget');
    }

    /**
     * @inheritdoc
     */
    public function getCMSDescription()
    {
        return Module::t('page', 'Widget for displaying content blocks on the page.');
    }

    /**
     * @inheritdoc
     */
    public function getEditView()
    {
        return '@app/modules/cms/backend/views/widget/content-widget/_form';
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->content;
    }
}