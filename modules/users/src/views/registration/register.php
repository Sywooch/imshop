<?php

use im\users\Module;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $module im\users\Module */
/* @var $model im\users\models\RegistrationForm */

$this->title = Module::t('registration', 'Sign up');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="registration-success">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (Yii::$app->getSession()->has('registration.success')) : ?>
        <div class="alert alert-success"><?= Yii::$app->getSession()->getFlash('registration.success') ?></div>
        <?php if (Yii::$app->getSession()->has('registration.info')) : ?>
            <div class="alert alert-info"><?= Yii::$app->getSession()->getFlash('registration.info') ?></div>
        <?php endif ?>
    <?php else: ?>

        <?php if (Yii::$app->getSession()->has('registration.error')) : ?>
            <div class="alert alert-danger"><?= Yii::$app->getSession()->getFlash('registration.error') ?></div>
        <?php endif ?>

        <?php $form = ActiveForm::begin(['id' => 'registration-form', 'enableClientValidation' => false]); ?>

        <?= $form->field($model, 'username') ?>

        <?= $form->field($model, 'email') ?>

        <?php if (!$module->passwordAutoGenerating): ?>
            <?= $form->field($model, 'password')->passwordInput() ?>
            <?= $form->field($model, 'password2')->passwordInput() ?>
        <?php endif ?>

        <?= $form->field($model, 'firstName') ?>

        <?= $form->field($model, 'lastName') ?>

        <div class="form-group">
            <?= Html::submitButton(Module::t('registration', 'Sign up'), ['class' => 'btn btn-primary', 'name' => 'register-button']) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <?= Html::a(Module::t('registration', 'Already registered? Sign in!'), ['/user/security/login']) ?>

    <?php endif ?>

</div>
