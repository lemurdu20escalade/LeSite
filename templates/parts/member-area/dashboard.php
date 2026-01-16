<?php
/**
 * Dashboard Espace Membre
 *
 * Hub page displaying widgets for tasks, documents, calendar, etc.
 *
 * @package Lemur
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$user = wp_get_current_user();
$member_name = lemur_get_member_name();
$is_bureau = is_lemur_bureau();

// Get widget data
$upcoming_tasks = lemur_get_upcoming_tasks(5);
$recent_documents = lemur_get_recent_documents(5);
$upcoming_events = function_exists('lemur_get_upcoming_events') ? lemur_get_upcoming_events(3) : [];
$user_collectifs = lemur_user_collectifs();
?>

<div class="member-dashboard">
    <!-- Header -->
    <header class="dashboard__header">
        <div class="dashboard__welcome">
            <h1 class="dashboard__title">
                <?php
                printf(
                    /* translators: %s: member first name */
                    esc_html__('Bonjour %s !', 'lemur'),
                    esc_html($member_name)
                );
                ?>
            </h1>
            <p class="dashboard__date"><?php echo esc_html(date_i18n('l j F Y')); ?></p>

            <?php if ($is_bureau): ?>
                <span class="dashboard__badge dashboard__badge--bureau">
                    <?php esc_html_e('Bureau', 'lemur'); ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="dashboard__user">
            <?php echo get_avatar($user->ID, 64, '', '', ['class' => 'dashboard__avatar']); ?>
            <a href="<?php echo esc_url(lemur_get_member_logout_url()); ?>" class="dashboard__logout">
                <?php esc_html_e('Se déconnecter', 'lemur'); ?>
            </a>
        </div>
    </header>

    <!-- Navigation rapide -->
    <?php lemur_render_member_nav('espace-membre'); ?>

    <!-- Widgets Grid -->
    <div class="dashboard__grid">

        <!-- Mes tâches -->
        <section class="dashboard__widget dashboard__widget--tasks" aria-labelledby="widget-tasks-title">
            <header class="widget__header">
                <h2 id="widget-tasks-title" class="widget__title">
                    <span class="widget__icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                        </svg>
                    </span>
                    <?php esc_html_e('Tâches à venir', 'lemur'); ?>
                </h2>
                <a href="<?php echo esc_url(home_url('/espace-membre/todo-list/')); ?>" class="widget__more">
                    <?php esc_html_e('Tout voir', 'lemur'); ?>
                </a>
            </header>

            <div class="widget__content">
                <?php if (!empty($upcoming_tasks)): ?>
                    <ul class="widget__list task-list">
                        <?php foreach ($upcoming_tasks as $task): ?>
                            <?php
                            $task_status = carbon_get_post_meta($task->ID, \Lemur\CustomPostTypes\Tasks::FIELD_STATUS);
                            $task_priority = carbon_get_post_meta($task->ID, \Lemur\CustomPostTypes\Tasks::FIELD_PRIORITY);
                            ?>
                            <li class="widget__item task-item task-item--<?php echo esc_attr($task_priority); ?>">
                                <span class="task-item__title"><?php echo esc_html(get_the_title($task)); ?></span>
                                <span class="task-item__due"><?php echo esc_html(lemur_format_task_due_date($task->ID)); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="widget__empty"><?php esc_html_e('Aucune tâche en cours.', 'lemur'); ?></p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Documents récents -->
        <section class="dashboard__widget dashboard__widget--documents" aria-labelledby="widget-docs-title">
            <header class="widget__header">
                <h2 id="widget-docs-title" class="widget__title">
                    <span class="widget__icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <path d="M14 2v6h6"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </span>
                    <?php esc_html_e('Documents récents', 'lemur'); ?>
                </h2>
                <a href="<?php echo esc_url(home_url('/espace-membre/documents/')); ?>" class="widget__more">
                    <?php esc_html_e('Tout voir', 'lemur'); ?>
                </a>
            </header>

            <div class="widget__content">
                <?php if (!empty($recent_documents)): ?>
                    <ul class="widget__list document-list">
                        <?php foreach ($recent_documents as $doc): ?>
                            <li class="widget__item document-item">
                                <a href="<?php echo esc_url(lemur_get_document_download_url($doc->ID)); ?>" class="document-item__link">
                                    <span class="document-item__title"><?php echo esc_html(get_the_title($doc)); ?></span>
                                    <span class="document-item__size"><?php echo esc_html(lemur_format_document_size($doc->ID)); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="widget__empty"><?php esc_html_e('Aucun document.', 'lemur'); ?></p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Événements à venir -->
        <section class="dashboard__widget dashboard__widget--events" aria-labelledby="widget-events-title">
            <header class="widget__header">
                <h2 id="widget-events-title" class="widget__title">
                    <span class="widget__icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </span>
                    <?php esc_html_e('Prochains événements', 'lemur'); ?>
                </h2>
                <a href="<?php echo esc_url(home_url('/espace-membre/calendrier-membres/')); ?>" class="widget__more">
                    <?php esc_html_e('Calendrier', 'lemur'); ?>
                </a>
            </header>

            <div class="widget__content">
                <?php if (!empty($upcoming_events)): ?>
                    <ul class="widget__list event-list">
                        <?php foreach ($upcoming_events as $event): ?>
                            <li class="widget__item event-item">
                                <span class="event-item__title"><?php echo esc_html(get_the_title($event)); ?></span>
                                <?php
                                $event_date = carbon_get_post_meta($event->ID, \Lemur\CustomPostTypes\Events::FIELD_DATE_START);
                                if ($event_date):
                                ?>
                                    <span class="event-item__date">
                                        <?php echo esc_html(date_i18n('j M', strtotime($event_date))); ?>
                                    </span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="widget__empty"><?php esc_html_e('Aucun événement prévu.', 'lemur'); ?></p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Mes collectifs -->
        <?php if (!empty($user_collectifs)): ?>
        <section class="dashboard__widget dashboard__widget--collectifs" aria-labelledby="widget-collectifs-title">
            <header class="widget__header">
                <h2 id="widget-collectifs-title" class="widget__title">
                    <span class="widget__icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                            <path d="M16 3.13a4 4 0 010 7.75"/>
                        </svg>
                    </span>
                    <?php esc_html_e('Mes collectifs', 'lemur'); ?>
                </h2>
            </header>

            <div class="widget__content">
                <ul class="widget__list collectif-list">
                    <?php foreach ($user_collectifs as $collectif): ?>
                        <li class="widget__item collectif-item">
                            <span class="collectif-item__name">
                                <?php echo esc_html(ucfirst(str_replace('collectif-', '', $collectif))); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
        <?php endif; ?>

        <!-- Accès rapides -->
        <section class="dashboard__widget dashboard__widget--quick-links" aria-labelledby="widget-links-title">
            <header class="widget__header">
                <h2 id="widget-links-title" class="widget__title">
                    <?php esc_html_e('Accès rapides', 'lemur'); ?>
                </h2>
            </header>

            <div class="widget__content">
                <nav class="quick-links" aria-label="<?php esc_attr_e('Liens rapides', 'lemur'); ?>">
                    <a href="<?php echo esc_url(home_url('/espace-membre/annuaire/')); ?>" class="quick-link">
                        <span class="quick-link__icon" aria-hidden="true">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                            </svg>
                        </span>
                        <span class="quick-link__label"><?php esc_html_e('Annuaire', 'lemur'); ?></span>
                    </a>

                    <a href="<?php echo esc_url(home_url('/espace-membre/calendrier-membres/')); ?>" class="quick-link">
                        <span class="quick-link__icon" aria-hidden="true">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </span>
                        <span class="quick-link__label"><?php esc_html_e('Calendrier', 'lemur'); ?></span>
                    </a>

                    <?php if ($is_bureau): ?>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=lemur_documents')); ?>" class="quick-link">
                        <span class="quick-link__icon" aria-hidden="true">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                        </span>
                        <span class="quick-link__label"><?php esc_html_e('Ajouter document', 'lemur'); ?></span>
                    </a>

                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=lemur_taches')); ?>" class="quick-link">
                        <span class="quick-link__icon" aria-hidden="true">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </span>
                        <span class="quick-link__label"><?php esc_html_e('Nouvelle tâche', 'lemur'); ?></span>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
        </section>

    </div>

    <!-- RGPD Notice -->
    <footer class="dashboard__footer">
        <p class="dashboard__privacy">
            <small>
                <?php esc_html_e('Vos données personnelles sont protégées conformément au RGPD. Seul votre prénom est visible dans l\'annuaire.', 'lemur'); ?>
            </small>
        </p>
    </footer>
</div>
