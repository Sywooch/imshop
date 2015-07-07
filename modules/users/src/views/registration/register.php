<?php

use im\users\Module;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $module im\users\Module */
/* @var $user im\users\models\User */
/* @var $profile im\users\models\Profile */

$this->title = Module::t('module', 'Sign up');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="registration-success">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(['id' => 'registration-form']); ?>

    <?= $form->field($user, 'username') ?>

    <?= $form->field($user, 'email') ?>

    <?php if (!$module->passwordAutoGenerating): ?>
        <?= $form->field($user, 'password')->passwordInput() ?>
    <?php endif ?>

    <div class="form-group">
        <?= Html::submitButton(Module::t('module', 'Sign up'), ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <?= Html::a(Module::t('module', 'Already registered? Sign in!'), ['/user/security/login']) ?>

</div>