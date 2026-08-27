<?php

/**
 * Reusable contextual help presentation.
 *
 * @var array{
 *     id: string,
 *     title: string,
 *     paragraphs?: list<string>,
 *     items?: list<string>,
 *     iconItems?: list<array{icon: string, label: string, description: string}>,
 *     example?: string|null,
 *     targetId?: string|null,
 *     anchor?: string|null,
 *     placement?: string|null,
 *     align?: string|null
 * } $contextualHelp
 */

$id           = $contextualHelp['id'];
$title        = $contextualHelp['title'];
$paragraphs   = $contextualHelp['paragraphs'] ?? [];
$items        = $contextualHelp['items'] ?? [];
$iconItems    = array_values(array_filter(
    $contextualHelp['iconItems'] ?? [],
    static fn (mixed $item): bool => is_array($item)
        && in_array($item['icon'] ?? null, ['edit', 'download', 'archive'], true)
        && is_string($item['label'] ?? null)
        && is_string($item['description'] ?? null),
));
$example      = $contextualHelp['example'] ?? null;
$targetId     = $contextualHelp['targetId'] ?? null;
$anchor       = in_array($contextualHelp['anchor'] ?? null, ['trigger', 'target'], true)
    ? $contextualHelp['anchor']
    : 'trigger';
$placement    = in_array(
    $contextualHelp['placement'] ?? null,
    ['right', 'left', 'top', 'bottom', 'inside-right', 'inside-left'],
    true,
) ? $contextualHelp['placement'] : 'right';
$align        = in_array($contextualHelp['align'] ?? null, ['start', 'center', 'end'], true)
    ? $contextualHelp['align']
    : 'center';
$titleId     = $id . '-title';
$contentId   = $id . '-content';
?>
<details
    class="context-help"
    data-context-help
    <?= $targetId !== null ? 'data-context-help-target="' . esc($targetId, 'attr') . '"' : '' ?>
    data-context-help-anchor="<?= esc($anchor, 'attr') ?>"
    data-context-help-placement="<?= esc($placement, 'attr') ?>"
    data-context-help-align="<?= esc($align, 'attr') ?>"
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

            <?php if ($iconItems !== []): ?>
                <ul class="context-help-icon-list">
                    <?php foreach ($iconItems as $iconItem): ?>
                        <li>
                            <span class="context-help-action-icon context-help-action-icon-<?= esc($iconItem['icon'], 'attr') ?>" aria-hidden="true">
                                <?php if ($iconItem['icon'] === 'edit'): ?>
                                    <svg viewBox="0 0 24 24"><path d="M4 20h4l11-11a2.8 2.8 0 0 0-4-4L4 16v4Z"></path><path d="m13.5 6.5 4 4"></path></svg>
                                <?php elseif ($iconItem['icon'] === 'download'): ?>
                                    <svg viewBox="0 0 24 24"><path d="M12 3v11"></path><path d="m7.5 10 4.5 4.5 4.5-4.5"></path><path d="M5 20h14"></path></svg>
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24"><path d="M4 7h16v13H4V7Z"></path><path d="M3 4h18v3H3V4Z"></path><path d="M9 11h6"></path></svg>
                                <?php endif ?>
                            </span>
                            <span class="context-help-icon-copy">
                                <strong><?= esc($iconItem['label']) ?></strong>
                                <span><?= esc($iconItem['description']) ?></span>
                            </span>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>

            <?php if ($example !== null): ?>
                <p class="context-help-example"><strong>Ejemplo:</strong> <?= esc($example) ?></p>
            <?php endif ?>
        </div>
    </div>
</details>
