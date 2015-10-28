<?php

namespace im\cms\models;

use creocoder\nestedsets\NestedSetsBehavior;
use im\base\behaviors\RelationsBehavior;
use im\base\traits\ModelBehaviorTrait;
use im\cms\Module;
use im\filesystem\components\FileInterface;
use im\filesystem\components\FilesBehavior;
use im\tree\models\Tree;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManagerStatic;
use yii\helpers\Inflector;

/**
 * Menu item model class.
 *
 * @property integer $id
 * @property string $label
 * @property string $title
 * @property string $url
 * @property bool $target_blank
 * @property string $rel
 * @property string $css_classes
 * @property string $visibility
 * @property integer $items_display
 * @property string $items_css_classes
 * @property bool $status
 *
 * @method MenuItemQuery parents(integer $depth = null)
 * @method MenuItemQuery children(integer $depth = null)
 * @method MenuItemQuery leaves()
 * @method MenuItemQuery prev()
 * @method MenuItemQuery next()
 */
class MenuItem extends Tree
{
    use ModelBehaviorTrait;

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    const DEFAULT_STATUS = self::STATUS_ACTIVE;

    const DISPLAY_DROPDOWN = 1;
    const DISPLAY_FULL_WIDTH_DROPDOWN = 2;
    const DISPLAY_GRID = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%menu_items}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'tree' => [
                'class' => NestedSetsBehavior::className(),
                'treeAttribute' => 'tree'
            ],
            'files' => [
                'class' => FilesBehavior::className(),
                'attributes' => [
                    'uploadedIcon' => [
                        'filesystem' => 'local',
                        'path' => '/menus',
                        'fileName' => function ($fileName, MenuItem $model) {
                            return Inflector::slug($model->label) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
                        },
                        'relation' => 'icon',
                        'deleteOnUnlink' => true,
                        'on beforeSave' => function (FileInterface $file) {
                            $image = ImageManagerStatic::make($file->getPath());
                            $image->resize(100, null, function (Constraint $constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            });
                            $image->save($file->getPath(), 100);
                        }
                    ],
                    'uploadedActiveIcon' => [
                        'filesystem' => 'local',
                        'path' => '/menus',
                        'fileName' => function ($fileName, MenuItem $model) {
                            return Inflector::slug($model->label) . '-active.' . pathinfo($fileName, PATHINFO_EXTENSION);
                        },
                        'relation' => 'activeIcon',
                        'deleteOnUnlink' => true,
                        'on beforeSave' => function (FileInterface $file) {
                            $image = ImageManagerStatic::make($file->getPath());
                            $image->resize(100, null, function (Constraint $constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            });
                            $image->save($file->getPath(), 100);
                        }
                    ],
                    'uploadedVideo' => [
                        'filesystem' => 'local',
                        'path' => '/menus',
                        'fileName' => function ($fileName, MenuItem $model) {
                            return Inflector::slug($model->label) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
                        },
                        'relation' => 'video',
                        'deleteOnUnlink' => true
                    ]
                ]
            ],
            'relations' => [
                'class' => RelationsBehavior::className(),
                'settings' => [
                    'icon' => ['deleteOnUnlink' => true],
                    'activeIcon' => ['deleteOnUnlink' => true],
                    'video' => ['deleteOnUnlink' => true]
                ],
                'relations' => [
                    'iconRelation' => $this->hasOne(MenuItemFile::className(), ['id' => 'icon_id']),
                    'activeIconRelation' => $this->hasOne(MenuItemFile::className(), ['id' => 'active_icon_id']),
                    'videoRelation' => $this->hasOne(MenuItemFile::className(), ['id' => 'video_id'])
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['label'], 'required'],
            [['label', 'title', 'url'], 'string', 'max' => 255],
            [['css_classes', 'rel', 'visibility', 'items_css_classes'], 'string', 'max' => 100],
            [['target_blank', 'status', 'items_display'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Module::t('menu-item', 'ID'),
            'label' => Module::t('menu-item', 'Label'),
            'title' => Module::t('menu-item', 'Title'),
            'url' => Module::t('menu-item', 'URL'),
            'target_blank' => Module::t('menu-item', ' Open in a new window/tab'),
            'rel' => Module::t('menu-item', ' Link relationship (XFN)'),
            'css_classes' => Module::t('menu-item', 'CSS classes'),
            'status' => Module::t('menu-item', 'Status'),
            'visibility' => Module::t('menu-item', 'Visibility'),
            'visibility' => Module::t('menu-item', 'Visibility'),
            'items_display' => Module::t('menu-item', 'Items display'),
            'items_css_classes' => Module::t('menu-item', 'Items CSS classes'),
            'uploadedIcon' => Module::t('menu-item', 'Icon'),
            'uploadedActiveIcon' => Module::t('menu-item', 'Active icon'),
            'uploadedVideo' => Module::t('menu-item', 'Video')
        ];
    }

    public function isVisible()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getHtmlAttributes()
    {
        return $this->css_classes ? ['class' => explode(' ', $this->css_classes)] : [];
    }

    public function getLinkHtmlAttributes()
    {
        $attributes = [];
        if ($this->target_blank) {
            $attributes['target'] = '_blank';
        }
        if ($this->rel) {
            $attributes['rel'] = $this->rel;
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new MenuItemQuery(get_called_class());
    }

    /**
     * @return array Statuses list
     */
    public static function getStatusesList()
    {
        return [
            self::STATUS_ACTIVE => Module::t('menu-item', 'Active'),
            self::STATUS_INACTIVE => Module::t('menu-item', 'Inactive')
        ];
    }

    /**
     * @return array
     */
    public static function getDisplayList()
    {
        return [
            self::DISPLAY_DROPDOWN => Module::t('menu-item', 'Dropdown'),
            self::DISPLAY_FULL_WIDTH_DROPDOWN => Module::t('menu-item', 'Full width dropdown'),
            self::DISPLAY_GRID => Module::t('menu-item', 'Grid (requires css class for items)')
        ];
    }
}
