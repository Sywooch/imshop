<?php

namespace im\catalog\models;

use creocoder\nestedsets\NestedSetsBehavior;
use im\search\components\query\facet\TermsFacetInterface;
use im\search\components\query\facet\TreeFacetInterface;
use im\search\models\Facet;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class CategoriesFacet extends Facet implements TermsFacetInterface, TreeFacetInterface
{
    const TYPE = 'categories_facet';

    /**
     * @var CategoriesFacetValue[]
     */
    protected $values = [];

    /**
     * @inheritdoc
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @inheritdoc
     */
    public function setValues($values)
    {
        parent::setValues($values);
        $this->values = $values;
    }

    /**
     * @inheritdoc
     */
    public function getValueInstance(array $config)
    {
        if (!isset($config['class'])) {
            $config['class'] = $this->getValueClass();
        }
        /** @var ActiveRecord $modelClass */
        $modelClass = $this->getModelClass();
        $instance = Yii::createObject($config);
        $attribute = $this->attribute_name ?: 'id';
        $category = $modelClass::findOne([$attribute => $instance->getKey()]);
        $instance->setEntity($category);

        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function getValueInstances(array $configs)
    {
        /** @var CategoriesFacetValue[] $instances */
        $instances = [];
        foreach ($configs as $config) {
            if (!isset($config['class'])) {
                $config['class'] = $this->getValueClass();
            }
            $instances[] = Yii::createObject($config);
        }
        $attribute = $this->attribute_name ?: 'id';
        /** @var ActiveRecord $modelClass */
        $modelClass = $this->getModelClass();
        $categories = $modelClass::find()
            ->where([$attribute => ArrayHelper::getColumn($instances, 'key')])->indexBy($attribute)->all();
        foreach ($instances as $key => $instance) {
            if (isset($categories[$instance->getKey()])) {
                $instance->setEntity($categories[$instance->getKey()]);
            }
        }

        return $instances;
    }

    /**
     * @inheritdoc
     */
    public function getValuesTree()
    {
        return $this->buildTree($this->getValues());
    }

    /**
     * Returns value class.
     *
     * @return string
     */
    protected function getValueClass()
    {
        return 'im\catalog\models\CategoriesFacetValue';
    }

    /**
     * Returns model class.
     *
     * @return string
     */
    protected function getModelClass()
    {
        return 'im\catalog\models\Category';
    }

    /**
     * @param CategoriesFacetValue[]|NestedSetsBehavior[] $nodes
     * @param int $left
     * @param int $right
     * @return CategoriesFacetValue[]
     */
    protected function buildTree($nodes, $left = 0, $right = null) {
        $tree = [];
        foreach ($nodes as $key => $node) {
            if ($node->getEntity()->{$node->getEntity()->leftAttribute} == $left + 1 && (is_null($right) || $node->getEntity()->{$node->getEntity()->rightAttribute} < $right)) {
                $tree[$key] = $node;
                if ($node->getEntity()->{$node->getEntity()->rightAttribute} - $node->getEntity()->{$node->getEntity()->leftAttribute} > 1) {
                    $node->setChildren($this->buildTree($nodes, $node->getEntity()->{$node->getEntity()->leftAttribute}, $node->getEntity()->{$node->getEntity()->rightAttribute}));
                } else {
                    $node->setChildren([]);
                }
                $left = $node->getEntity()->{$node->getEntity()->rightAttribute};
            }
        }

        return $tree;
    }
}