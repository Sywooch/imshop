<?php

use im\cms\models\Menu;
use im\cms\Module;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model im\cms\widgets\MenuWidget */
/* @var $form yii\widgets\ActiveForm|im\forms\components\DynamicActiveForm */

$fieldConfig = isset($fieldConfig) ? $fieldConfig : [];

?>

<?php if (!isset($form)) {
    $form = ActiveForm::begin(['id' => 'menu-widget-form', 'options' => ['data-pjax' => 1]]);
} ?>

<?= $form->field($model, 'title', $fieldConfig) ?>

<?= $form->field($model, 'model_id', $fieldConfig)->dropDownList(
    ArrayHelper::map(Menu::find()->asArray()->orderBy('name')->all(), 'id', 'name'),
    ['prompt' => '']
) ?>

<?php if (!isset($form)) {
    echo Html::submitButton(Module::t('module', 'Save'), ['class' => $model->isNewRecord ? 'btn btn-primary' : 'btn btn-success']);
    ActiveForm::end();
} ?>