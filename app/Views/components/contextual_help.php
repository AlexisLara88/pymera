<?php

/**
 * Reusable contextual help presentation.
 *
 * @var string       $id
 * @var string       $title
 * @var list<string> $paragraphs
 * @var list<string> $items
 * @var string|null  $example
 */

$paragraphs = $paragraphs ?? [];
$items      = $items ?? [];
$example    = $example ?? null;
$titleId    = $id . '-title';
$contentId  = $id . '-content';
?>
<details class="context-help" data-context-help>
    <summary
        class="context-help-trigger"
        aria-label="Abrir ayuda: <?= esc($title, 'attr') ?>"
        aria-controls="<?= esc($contentId, 'attr') ?>"
        aria-expanded="false"
    >
        <span aria-hidden="true">?</span>
    </summary>
    <div
        class="context-help-card"
        id="<?= esc($contentId, 'attr') ?>"
        role="region"
        aria-labelledby="<?= esc($titleId, 'attr') ?>"
    >
        <header class="context-help-card-header">
            <div>
                <span>Ayuda de esta sección</span>
                <strong id="<?= esc($titleId, 'attr') ?>"><?= esc($title) ?></strong>
            </div>
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
