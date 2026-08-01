<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Bootstrap-style pagination renderer.
 */
class Pagination
{
    public static function render(int $total, int $perPage, int $current): string
    {
        if ($total <= 0) {
            return '';
        }
        $pages = (int) ceil($total / $perPage);
        if ($pages <= 1) {
            return '';
        }
        $current = max(1, min($current, $pages));

        $query = $_GET;
        unset($query['page']);
        $queryString = http_build_query($query);
        $queryString = $queryString !== '' ? '&' . $queryString : '';

        $url = function (int $page) use ($queryString): string {
            return '?page=' . $page . $queryString;
        };

        $html = '<nav aria-label="Pagination"><ul class="pagination pagination-sm justify-content-end mb-0">';

        if ($current > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . e($url($current - 1)) . '"><i class="fa-solid fa-chevron-left"></i></a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-left"></i></span></li>';
        }

        $start = max(1, $current - 2);
        $end = min($pages, $current + 2);
        if ($start > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . e($url(1)) . '">1</a></li>';
            if ($start > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            }
        }
        for ($i = $start; $i <= $end; $i++) {
            $class = $i === $current ? ' active' : '';
            $html .= '<li class="page-item' . $class . '"><a class="page-link" href="' . e($url($i)) . '">' . $i . '</a></li>';
        }
        if ($end < $pages) {
            if ($end < $pages - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            }
            $html .= '<li class="page-item"><a class="page-link" href="' . e($url($pages)) . '">' . $pages . '</a></li>';
        }

        if ($current < $pages) {
            $html .= '<li class="page-item"><a class="page-link" href="' . e($url($current + 1)) . '"><i class="fa-solid fa-chevron-right"></i></a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-right"></i></span></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }
}
