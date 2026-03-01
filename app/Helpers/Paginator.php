<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Request;

/**
 * Paginator — Lightweight pagination calculator for API responses and web views.
 *
 * Computes all pagination metadata from three integers (total record count,
 * the current page number, and the page size) so that controllers and
 * service layer methods never have to duplicate the arithmetic.
 *
 * Usage in a controller:
 *
 *   $paginator = Paginator::fromRequest($total, $request);
 *   $rows      = $model->list($storeId, $paginator->offset(), $paginator->perPage);
 *
 *   // In an API response:
 *   Response::json(['success' => true, 'data' => $rows, 'meta' => $paginator->toArray()]);
 *
 *   // In a view:
 *   $meta = $paginator->toArray();   // pass to template
 *
 * The PER_PAGE constant is defined in config/app.php (default: 25).
 */
class Paginator
{
    /** @var int Total number of records in the full (unsliced) result set. */
    private int $total;

    /** @var int The page number currently being displayed (1-indexed). */
    private int $currentPage;

    /** @var int Maximum number of records per page. */
    public int $perPage;

    /**
     * Construct a Paginator for the given total, page, and page-size.
     *
     * The current page is clamped to the range [1, totalPages()] to prevent
     * negative offsets or pages beyond the last page from causing SQL errors.
     *
     * @param int $total       Total number of records across all pages.
     * @param int $currentPage The requested page number (1-indexed).
     * @param int $perPage     Number of records per page. Defaults to PER_PAGE constant.
     */
    public function __construct(int $total, int $currentPage, int $perPage = 0)
    {
        // Resolve default perPage from the constant defined in config/app.php.
        if ($perPage <= 0) {
            $perPage = defined('PER_PAGE') ? (int) PER_PAGE : 25;
        }

        $this->total    = max(0, $total);
        $this->perPage  = max(1, $perPage);

        // Calculate the maximum valid page first, then clamp currentPage.
        $maxPage           = $this->totalPages();
        $this->currentPage = max(1, min($currentPage, $maxPage === 0 ? 1 : $maxPage));
    }

    // -----------------------------------------------------------------------
    // Derived values
    // -----------------------------------------------------------------------

    /**
     * Return the total number of pages needed to display all records.
     *
     * Uses integer ceiling division.  Returns at least 1 even when total is 0
     * so that consumers can always render a "Page 1 of 1" label.
     *
     * @return int Total page count (>= 1).
     */
    public function totalPages(): int
    {
        if ($this->total === 0) {
            return 1;
        }

        return (int) ceil($this->total / $this->perPage);
    }

    /**
     * Return the SQL OFFSET value for the current page.
     *
     * Suitable for use directly in a PDO prepared statement:
     *   SELECT * FROM products LIMIT ? OFFSET ?
     *
     * @return int Zero-indexed byte offset (>= 0).
     */
    public function offset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    /**
     * Determine whether a next page exists.
     *
     * @return bool True when the current page is not the last page.
     */
    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages();
    }

    /**
     * Determine whether a previous page exists.
     *
     * @return bool True when the current page is greater than 1.
     */
    public function hasPrev(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Serialise pagination metadata to an associative array.
     *
     * The returned shape is used as the 'meta' key in every paginated API
     * response envelope and is also passed to view templates for rendering
     * pagination controls.
     *
     * Envelope shape (from CLAUDE.md):
     *   { "success": true, "data": [...], "meta": { ... }, "error": null }
     *
     * @return array{
     *     total:        int,
     *     per_page:     int,
     *     current_page: int,
     *     total_pages:  int,
     *     offset:       int
     * }
     */
    public function toArray(): array
    {
        return [
            'total'        => $this->total,
            'per_page'     => $this->perPage,
            'current_page' => $this->currentPage,
            'total_pages'  => $this->totalPages(),
            'offset'       => $this->offset(),
        ];
    }

    // -----------------------------------------------------------------------
    // Factory
    // -----------------------------------------------------------------------

    /**
     * Build a Paginator by reading the ?page= query parameter from a Request.
     *
     * The page number is coerced to an integer and clamped to >= 1 so that
     * invalid query strings (e.g. ?page=abc or ?page=-5) never cause errors.
     * The per-page value always comes from the PER_PAGE constant — callers
     * cannot override it via a query parameter (prevents LIMIT abuse).
     *
     * @param int     $total   Total number of records for this query.
     * @param Request $request The current HTTP request (used to read ?page=).
     *
     * @return self A configured Paginator instance.
     */
    public static function fromRequest(int $total, Request $request): self
    {
        $pageParam = $request->query('page', '1');
        $page      = max(1, (int) filter_var($pageParam, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]));

        return new self($total, $page);
    }
}
