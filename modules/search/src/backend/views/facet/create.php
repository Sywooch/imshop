<?php

use im\search\Module;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model im\search\models\Facet */

$this->title = Module::t('facet', 'Facets');
$this->params['subtitle'] = Module::t('facet', 'Facet creation');
$this->params['breadcrumbs'] = [['label' => $this->title, 'url' => ['index']], $this->params['subtitle']];
?>
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><?= $this->params['subtitle'] ?></h3>
        <div class="box-tools pull-right">
            <?= Html::a('<i class="fa fa-reply"></i>', ['index'], ['class' => 'btn btn-sm btn-default', 'title' => Module::t('module', 'Cancel')]) ?>
        </div>
    </div>
    <div class="box-body">
        <?= $model->getEditView() ? $this->render($model->getEditView(), ['model' => $model]) : '' ?>
    </div>
</div>
