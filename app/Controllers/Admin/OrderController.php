<?php 

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Order;
use App\Models\Customer;

/**
 * Admin Order Controller
 * 
 * Handles all admin order operations:
 * - List all orders
 * - View order details
 * - Update order status
 * - Search and filter orders
 * - Get order statistics
 */
class OrderController extends BaseController
{
    private Order $orderModel;
    private Customer $customerModel;

    public function __construct($db, $request, $response)
    {
        parent::__construct($db, $request, $response);
        $this->orderModel = new Order($db);
        $this->customerModel = new Customer($db);
    }

    /**
     * Step 1: Get all orders with pagination and filters
     * GET /admin/orders
     * 
     * Query params:
     * - page: pagination page (default 1)
     * - limit: items per page (default 20)
     * - status: filter by status (pending, completed, cancelled, etc)
     * - search: search by order_id, customer email, or customer name
     */
    public function index()
    {
        $this->requireAdmin();

        $page = (int) ($this->request->query('page') ?? 1);
        $limit = (int) ($this->request->query('limit') ?? 20);
        $status = $this->request->query('status');
        $search = $this->request->query('search');

        $offset = ($page - 1) * $limit;

        // Build dynamic query
        $where = [];
        $params = [];

        if ($status) {
            $where[] = "o.status = ?";
            $params[] = $status;
        }

        if ($search) {
            $where[] = "(o.order_id LIKE ? OR u.email LIKE ? OR u.first_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM orders o 
                     LEFT JOIN users u ON o.user_id = u.id 
                     $whereClause";
        $countResult = $this->db->fetch($countSql, $params);
        $total = $countResult['total'] ?? 0;

        // Get paginated orders with customer info
        $sql = "SELECT o.*, 
                       u.email, u.first_name, u.last_name, u.phone
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                $whereClause
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $orders = $this->db->fetchAll($sql, $params);

        return $this->response->success([
            'data' => $orders,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ], 'Orders retrieved successfully', 200);
    }

    /**
     * Step 2: Get single order with full details
     * GET /admin/orders/:id
     * 
     * Returns:
     * - Order details
     * - Customer information
     * - Order items
     * - Payment information
     */
    public function show($id)
    {
        $this->requireAdmin();

        $id = (int) $id;

        // Get order with user info
        $sql = "SELECT o.*, 
                       u.email, u.first_name, u.last_name, u.phone, u.address
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = ?";
        
        $order = $this->db->fetch($sql, [$id]);

        if (!$order) {
            return $this->response->error('Order not found', 404);
        }

        // Get order items with product details
        $order['items'] = $this->orderModel->getOrderItems($id);

        // Get payment information if exists
        $paymentSql = "SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1";
        $order['payment'] = $this->db->fetch($paymentSql, [$id]);

        return $this->response->success($order, 'Order retrieved successfully', 200);
    }

    /**
     * Step 3: Update order status
     * PUT /admin/orders/:id/status
     * 
     * Request body:
     * {
     *   "status": "completed|cancelled|shipped|pending"
     * }
     */
    public function updateStatus($id)
    {
        $this->requireAdmin();

        $id = (int) $id;
        $data = $this->request->post();

        // Validate request
        if (!isset($data['status'])) {
            return $this->response->error('Status is required', 400);
        }

        $status = trim($data['status']);
        $validStatuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            return $this->response->error('Invalid status', 400);
        }

        // Check if order exists
        $order = $this->orderModel->find($id);
        if (!$order) {
            return $this->response->error('Order not found', 404);
        }

        // Update status
        $updated = $this->orderModel->updateStatus($id, $status);

        if (!$updated) {
            return $this->response->error('Failed to update order', 500);
        }

        // Log the action
        $this->log("Order #$id status updated to $status by admin #{$this->authUser['admin_id']}", 'info');

        return $this->response->success(null, 'Order status updated successfully', 200);
    }

    /**
     * Step 4: Get order statistics and summary
     * GET /admin/orders/stats
     * 
     * Returns:
     * - Total orders
     * - Orders by status
     * - Total revenue
     * - Pending orders count
     */
    public function stats()
    {
        $this->requireAdmin();

        $stats = [];

        // Total orders
        $totalSql = "SELECT COUNT(*) as count FROM orders";
        $stats['total_orders'] = $this->db->fetch($totalSql)['count'] ?? 0;

        // Orders by status
        $statusSql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
        $statusResults = $this->db->fetchAll($statusSql);
        $stats['orders_by_status'] = [];
        foreach ($statusResults as $status) {
            $stats['orders_by_status'][$status['status']] = $status['count'];
        }

        // Total revenue
        $revenueSql = "SELECT SUM(final_amount) as total FROM orders WHERE status = 'completed'";
        $stats['total_revenue'] = (float) ($this->db->fetch($revenueSql)['total'] ?? 0);

        // Pending orders
        $stats['pending_orders'] = $this->orderModel->countPending();

        // Today's orders
        $todaySql = "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()";
        $stats['today_orders'] = $this->db->fetch($todaySql)['count'] ?? 0;

        // Today's revenue
        $todayRevenueSql = "SELECT SUM(final_amount) as total FROM orders 
                           WHERE DATE(created_at) = CURDATE() AND status = 'completed'";
        $stats['today_revenue'] = (float) ($this->db->fetch($todayRevenueSql)['total'] ?? 0);

        return $this->response->success($stats, 'Order statistics retrieved successfully', 200);
    }

    /**
     * Step 5: Search orders by various criteria
     * GET /admin/orders/search
     * 
     * Query params:
     * - q: search query (order_id, customer email, customer name)
     * - status: filter by status
     * - date_from: filter from date (Y-m-d)
     * - date_to: filter to date (Y-m-d)
     */
    public function search()
    {
        $this->requireAdmin();

        $query = $this->request->query('q');
        $status = $this->request->query('status');
        $dateFrom = $this->request->query('date_from');
        $dateTo = $this->request->query('date_to');

        if (!$query && !$status && !$dateFrom && !$dateTo) {
            return $this->response->error('Please provide search criteria', 400);
        }

        $where = [];
        $params = [];

        if ($query) {
            $where[] = "(o.order_id LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $params[] = "%$query%";
            $params[] = "%$query%";
            $params[] = "%$query%";
            $params[] = "%$query%";
        }

        if ($status) {
            $where[] = "o.status = ?";
            $params[] = $status;
        }

        if ($dateFrom) {
            $where[] = "DATE(o.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = "DATE(o.created_at) <= ?";
            $params[] = $dateTo;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "SELECT o.*, u.email, u.first_name, u.last_name
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                $whereClause
                ORDER BY o.created_at DESC
                LIMIT 50";

        $results = $this->db->fetchAll($sql, $params);

        return $this->response->success($results, 'Orders searched successfully', 200);
    }

    /**
     * Step 6: Get order items detail
     * GET /admin/orders/:id/items
     */
    public function items($id)
    {
        $this->requireAdmin();

        $id = (int) $id;

        // Verify order exists
        $order = $this->orderModel->find($id);
        if (!$order) {
            return $this->response->error('Order not found', 404);
        }

        $items = $this->orderModel->getOrderItems($id);

        return $this->response->success($items, 'Order items retrieved successfully', 200);
    }

    /**
     * Step 7: Cancel an order
     * POST /admin/orders/:id/cancel
     */
    public function cancel($id)
    {
        $this->requireAdmin();

        $id = (int) $id;

        $order = $this->orderModel->find($id);
        if (!$order) {
            return $this->response->error('Order not found', 404);
        }

        if ($order['status'] === 'completed' || $order['status'] === 'shipped') {
            return $this->response->error('Cannot cancel a completed or shipped order', 400);
        }

        $updated = $this->orderModel->updateStatus($id, 'cancelled');

        if (!$updated) {
            return $this->response->error('Failed to cancel order', 500);
        }

        $this->log("Order #$id cancelled by admin #{$this->authUser['admin_id']}", 'info');

        return $this->response->success(null, 'Order cancelled successfully', 200);
    }
}
