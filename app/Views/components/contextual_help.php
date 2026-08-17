<?php

/**
 * Reusable contextual help presentation.
 *
 * @var array{
 *     id: string,
 *     title: string,
 *     paragraphs?: list<string>,
 *     items?: list<string>,
 *     example?: string|null,
 *     targetId?: string|null
 * } $contextualHelp
 */

$id           = $contextualHelp['id'];
$title        = $contextualHelp['title'];
$paragraphs   = $contextualHelp['paragraphs'] ?? [];
$items        = $contextualHelp['items'] ?? [];
$example      = $contextualHelp['example'] ?? null;
$targetId     = $contextualHelp['targetId'] ?? null;
$titleId     = $id . '-title';
$contentId   = $id . '-content';
?>
<details
    class="context-help"
    data-context-help
    <?= $targetId !== null ? 'data-context-help-target="' . esc($targetId, 'attr') . '"' : '' ?>
>
    <summary
        class="context-help-trigger"
        aria-label="Abrir ayuda: <?= esc($title, 'attr') ?>"
        aria-controls="<?= esc($contentId, 'attr') ?>"
        aria-expanded="false"
        aria-haspopup="dialog"
    >
        <span aria-hidden="true">?</span>
    </summary>
    <div
        class="context-help-card"
        id="<?= esc($contentId, 'attr') ?>"
        role="dialog"
        aria-labelledby="<?= esc($titleId, 'attr') ?>"
    >
        <header class="context-help-card-header" data-context-help-drag-handle title="Arrastrá para mover">
            <strong id="<?= esc($titleId, 'attr') ?>"><?= esc($title) ?></strong>
            <button class="context-help-close" type="button" aria-label="Cerrar ayuda" data-context-help-close>
                <span aria-hidden="true">×</span>
            </button>
        </header>

        <div class="context-help-copy">
            <?php foreach ($paragraphs as $paragraph): ?>
                <p><?= esc($paragraph) ?></p>
            <?php endforeach ?>

            <?php if ($items !== []): ?>
                <ul>
                    <?php foreach ($items as $item): ?>
                        <li><?= esc($item) ?></li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>

            <?php if ($example !== null): ?>
                <p class="context-help-example"><strong>Ejemplo:</strong> <?= esc($example) ?></p>
            <?php endif ?>
        </div>
    </div>
</details>
