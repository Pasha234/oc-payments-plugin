<?php if (!$this->fatalError): ?>

<?php Block::put('form-contents') ?>

<div class="layout-row min-size">
    <?= $this->formRenderOutsideFields() ?>

    <div class="form-buttons">
        <div class="loading-indicator-container">
            <a
                    href="javascript:;"
                    class="btn btn-primary oc-icon-check save"
                    data-request="onSave"
                    data-load-indicator="<?= e(trans('backend::lang.form.saving')) ?>"
                    data-request-before-update="$(this).trigger('unchange.oc.changeMonitor')"
                    data-hotkey="ctrl+s, cmd+s">
                <?= e(trans('backend::lang.form.save')) ?>
            </a>
            <a
                    href="javascript:;"
                    class="btn btn-primary oc-icon-check save"
                    data-request-before-update="$(this).trigger('unchange.oc.changeMonitor')"
                    data-request="onSave"
                    data-request-data="close:1"
                    data-load-indicator="<?= e(trans('backend::lang.form.saving')) ?>">
                <?= e(trans('backend::lang.form.save_and_close')) ?>
            </a>
                <span class="btn-text">
                    <?= e(trans('backend::lang.form.or')) ?> <a href="<?= Backend::url('palpalych/payments/paymentmethods') ?>"><?= e(trans('backend::lang.form.cancel')) ?></a>
                </span>
        </div>
    </div>

</div>
<div class="layout-row">
    <?= $this->formRenderPrimaryTabs() ?>
</div>

<?php Block::endPut() ?>

<?php Block::put('form-sidebar') ?>
    <div class="hide-tabs"><?= $this->formRenderSecondaryTabs() ?></div>
<?php Block::endPut() ?>

<?php Block::put('body') ?>
    <?= Form::open([
        'class'=>'layout stretch',
        'data-change-monitor' => 'true',
        'id' => 'review-form'
    ]) ?>
    <?= $this->makeLayout('form-with-sidebar') ?>
    <?= Form::close() ?>
<?php Block::endPut() ?>

<?php else: ?>
<div class="control-breadcrumb">
    <?= Block::placeholder('breadcrumb') ?>
</div>
<div class="padded-container">
    <p class="flash-message static error"><?= e(trans($this->fatalError)) ?></p>
    <p><a href="<?= Backend::url('palpalych/payments/paymentmethods') ?>" class="btn btn-default"><?= e(trans('backend::lang.form.return_to_list')) ?></a></p>
</div>
<?php endif ?>
