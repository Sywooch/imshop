<?php

namespace im\catalog\models;

use im\seo\models\Meta;

/**
 * Category meta model class.
 */
class CategoryMeta extends Meta
{
    const ENTITY_TYPE = 'category_meta';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%category_meta}}';
    }

//    /**
//     * @return \yii\db\ActiveQuery
//     */
//    public function getEntity()
//    {
//        return $this->hasOne(Category::className(), ['id' => 'entity_id']);
//    }
}
