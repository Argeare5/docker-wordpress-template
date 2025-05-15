<?php
// Custom routing for /dashboard/{username}/

add_action('init', function() {
    add_rewrite_rule('^dashboard/([^/]+)/?$', 'index.php?solvera_dashboard_user=$matches[1]', 'top');
    add_rewrite_tag('%solvera_dashboard_user%', '([^&]+)');
});

add_action('template_redirect', function() {
    $dashboard_user = get_query_var('solvera_dashboard_user');
    if ($dashboard_user) {
        $user = get_user_by('login', $dashboard_user);
        if ($user && is_user_logged_in() && get_current_user_id() == $user->ID) {
            // Show dashboard only for the user themselves
            echo do_shortcode('[solvera_dashboard_menu]');
            echo do_shortcode('[solvera_user_dashboard]');
            exit;
        } else {
            // 404 if not found or doesn't match
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            include(get_query_template('404'));
            exit;
        }
    }
}); 