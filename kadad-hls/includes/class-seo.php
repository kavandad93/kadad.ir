<?php
/**
 * SEO Class
 * Handles SEO checks
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHC_Seo
{
    /**
     * Check all SEO aspects
     */
    public function check_all()
    {
        $issues = [];
        $recommendations = [];
        $score = 100;
        
        // Title check
        if (!$this->has_title()) {
            $score -= 20;
            $issues[] = __('No title tag found', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Add a title tag to your site', KHC_TEXT_DOMAIN);
        }
        
        // Meta description
        if (!$this->has_meta_description()) {
            $score -= 15;
            $issues[] = __('No meta description found', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Add a meta description for better SEO', KHC_TEXT_DOMAIN);
        }
        
        // Canonical URL
        if (!$this->has_canonical()) {
            $score -= 10;
            $issues[] = __('No canonical URL found', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Add canonical URLs to prevent duplicate content', KHC_TEXT_DOMAIN);
        }
        
        // Robots.txt
        if (!$this->has_robots()) {
            $score -= 5;
            $issues[] = __('No robots.txt file found', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Create a robots.txt file to guide search engines', KHC_TEXT_DOMAIN);
        }
        
        // Sitemap
        if (!$this->has_sitemap()) {
            $score -= 10;
            $issues[] = __('No sitemap found', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Create and submit a sitemap to search engines', KHC_TEXT_DOMAIN);
        }
        
        // Open Graph
        if (!$this->has_open_graph()) {
            $score -= 10;
            $issues[] = __('Open Graph tags missing', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Add Open Graph tags for better social sharing', KHC_TEXT_DOMAIN);
        }
        
        // Twitter Cards
        if (!$this->has_twitter_cards()) {
            $score -= 5;
            $issues[] = __('Twitter Cards tags missing', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Add Twitter Cards for better Twitter sharing', KHC_TEXT_DOMAIN);
        }
        
        // Schema markup
        if (!$this->has_schema()) {
            $score -= 10;
            $issues[] = __('No Schema markup found', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Add Schema markup for rich snippets', KHC_TEXT_DOMAIN);
        }
        
        // Broken links
        $broken_links = $this->check_broken_links();
        if ($broken_links > 0) {
            $score -= min($broken_links * 2, 15);
            $issues[] = sprintf(__('%d broken links found', KHC_TEXT_DOMAIN), $broken_links);
            $recommendations[] = __('Fix or remove broken links', KHC_TEXT_DOMAIN);
        }
        
        // Headings structure
        $heading_issues = $this->check_headings();
        if (!empty($heading_issues)) {
            $score -= 10;
            $issues = array_merge($issues, $heading_issues);
            $recommendations[] = __('Use proper heading structure (H1 > H2 > H3)', KHC_TEXT_DOMAIN);
        }
        
        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'recommendations' => $recommendations,
            'details' => [
                'has_title' => $this->has_title(),
                'has_meta_description' => $this->has_meta_description(),
                'has_canonical' => $this->has_canonical(),
                'has_robots' => $this->has_robots(),
                'has_sitemap' => $this->has_sitemap(),
                'has_open_graph' => $this->has_open_graph(),
                'has_twitter_cards' => $this->has_twitter_cards(),
                'has_schema' => $this->has_schema(),
                'broken_links' => $broken_links,
                'heading_issues' => $heading_issues
            ]
        ];
    }

    /**
     * Check if site has title
     */
    private function has_title()
    {
        $homepage = get_home_url();
        $response = wp_remote_get($homepage, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return preg_match('/<title[^>]*>.*?<\/title>/i', $body) === 1;
    }

    /**
     * Check if site has meta description
     */
    private function has_meta_description()
    {
        $homepage = get_home_url();
        $response = wp_remote_get($homepage, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return preg_match('/<meta[^>]*name=["\']description["\'][^>]*>/i', $body) === 1;
    }

    /**
     * Check if site has canonical
     */
    private function has_canonical()
    {
        $homepage = get_home_url();
        $response = wp_remote_get($homepage, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return preg_match('/<link[^>]*rel=["\']canonical["\'][^>]*>/i', $body) === 1;
    }

    /**
     * Check if robots.txt exists
     */
    private function has_robots()
    {
        $robots_url = home_url('robots.txt');
        $response = wp_remote_get($robots_url, ['timeout' => 5]);
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    /**
     * Check if sitemap exists
     */
    private function has_sitemap()
    {
        $sitemap_urls = [
            home_url('sitemap.xml'),
            home_url('sitemap_index.xml'),
            home_url('sitemap_index.php')
        ];
        
        foreach ($sitemap_urls as $url) {
            $response = wp_remote_get($url, ['timeout' => 5]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if Open Graph tags exist
     */
    private function has_open_graph()
    {
        $homepage = get_home_url();
        $response = wp_remote_get($homepage, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return preg_match('/<meta[^>]*property=["\']og:/i', $body) === 1;
    }

    /**
     * Check if Twitter Cards exist
     */
    private function has_twitter_cards()
    {
        $homepage = get_home_url();
        $response = wp_remote_get($homepage, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return preg_match('/<meta[^>]*name=["\']twitter:/i', $body) === 1;
    }

    /**
     * Check if Schema markup exists
     */
    private function has_schema()
    {
        $homepage = get_home_url();
        $response = wp_remote_get($homepage, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return preg_match('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>/i', $body) === 1;
    }

    /**
     * Check broken links
     */
    private function check_broken_links()
    {
        $homepage = get_home_url();
        $response = wp_remote_get($homepage, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return 0;
        }
        
        $body = wp_remote_retrieve_body($response);
        preg_match_all('/<a[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $body, $links);
        
        $broken_count = 0;
        $checked = 0;
        
        foreach ($links[1] as $link) {
            if ($checked > 20) break; // Limit to 20 links
            
            // Skip external links and anchors
            if (strpos($link, 'http') !== 0 || strpos($link, '#') === 0) {
                continue;
            }
            
            $link_response = wp_remote_get($link, ['timeout' => 5]);
            if (is_wp_error($link_response) || wp_remote_retrieve_response_code($link_response) >= 400) {
                $broken_count++;
            }
            
            $checked++;
        }
        
        return $broken_count;
    }

    /**
     * Check heading structure
     */
    private function check_headings()
    {
        $issues = [];
        $homepage = get_home_url();
        $response = wp_remote_get($homepage, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return $issues;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Check for H1
        if (preg_match('/<h1[^>]*>/i', $body) !== 1) {
            $issues[] = __('No H1 heading found', KHC_TEXT_DOMAIN);
        }
        
        // Check for multiple H1s
        if (preg_match_all('/<h1[^>]*>/i', $body, $matches) > 1) {
            $issues[] = __('Multiple H1 headings found', KHC_TEXT_DOMAIN);
        }
        
        // Check heading hierarchy
        preg_match_all('/<h([1-6])[^>]*>/i', $body, $matches);
        $headings = $matches[1];
        
        if (!empty($headings)) {
            $last_level = 0;
            foreach ($headings as $level) {
                if ($level > $last_level + 1 && $last_level > 0) {
                    $issues[] = __('Heading hierarchy is broken (skipping levels)', KHC_TEXT_DOMAIN);
                    break;
                }
                $last_level = $level;
            }
        }
        
        return $issues;
    }
}