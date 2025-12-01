<?php
namespace App\Services;

use PDO;

/**
 * DashboardService: Provides generic dashboard statistics and activity calculations
 * 
 * This service extracts common dashboard operations (stats computation, recent activity,
 * user/family data aggregation) that are useful for both admin and user dashboards.
 * 
 * Promotes code reuse and consistency across the application's dashboard views.
 */
class DashboardService
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo = null, $logger = null)
    {
        $this->pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
        $this->logger = $logger;

        if (!$this->pdo) {
            throw new \RuntimeException('DashboardService requires a PDO connection (provide via constructor or $GLOBALS[\'pdo\'])');
        }
    }

    /**
     * Get comprehensive dashboard statistics
     * 
     * Includes user counts, role distribution, event stats, payment/donation totals,
     * family member stats (adults/kids), and monthly activity metrics.
     * 
     * @return array Dashboard stats with keys like total_users, active_users, total_events, etc.
     */
    public function getDashboardStats()
    {
        $stats = [];

        // Detect database driver
    $dateFunction = "DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $currentDate = "NOW()";

        // Total users
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Active users (users who logged in within last 30 days)
        $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT user_id) as count FROM sessions WHERE last_activity > $dateFunction");
        $stmt->execute();
        $stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Inactive users
        $stats['inactive_users'] = $stats['total_users'] - $stats['active_users'];

        // Total events
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM events");
        $stats['total_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Upcoming events
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM events WHERE start_at > $currentDate");
        $stats['upcoming_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Past events
        $stats['past_events'] = $stats['total_events'] - $stats['upcoming_events'];

        // Total donations (completed payments)
        $stmt = $this->pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_donations'] = $result['total'] ?? 0;

        // Total payments (all)
        $stmt = $this->pdo->query("SELECT SUM(amount) as total FROM payments");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_payments'] = $result['total'] ?? 0;

        // Total revenue (donations + payments)
        $stats['total_revenue'] = $stats['total_donations'] + $stats['total_payments'];

        // Monthly revenue (current month) - MySQL syntax
        $stmt = $this->pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed' AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['monthly_revenue'] = $result['total'] ?? 0;

        // Total members (family members)
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM family_members");
        $stats['total_members'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total families (distinct user_ids in family_members)
        $stmt = $this->pdo->query("SELECT COUNT(DISTINCT user_id) as count FROM family_members");
        $stats['total_families'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Calculate age groups: Kids (≤10), Adults (11-59), Seniors (≥60)
        $stmt = $this->pdo->query("
            SELECT 
                SUM(CASE WHEN birth_year IS NULL THEN 0 
                         WHEN (YEAR(NOW()) - birth_year) <= 10 THEN 1 
                         ELSE 0 END) as kids,
                SUM(CASE WHEN birth_year IS NULL THEN 0 
                         WHEN (YEAR(NOW()) - birth_year) BETWEEN 11 AND 59 THEN 1 
                         ELSE 0 END) as adults,
                SUM(CASE WHEN birth_year IS NULL THEN 0 
                         WHEN (YEAR(NOW()) - birth_year) >= 60 THEN 1 
                         ELSE 0 END) as seniors
            FROM family_members
        ");
        $ageGroups = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_kids'] = (int)($ageGroups['kids'] ?? 0);
        $stats['total_adults'] = (int)($ageGroups['adults'] ?? 0);
        $stats['total_seniors'] = (int)($ageGroups['seniors'] ?? 0);

        // Role-based user counts
        $stmt = $this->pdo->query("SELECT r.name, COUNT(u.id) as count FROM roles r LEFT JOIN users u ON r.id = u.role_id GROUP BY r.id, r.name");
        $roleStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($roleStats as $roleStat) {
            $roleKey = strtolower(str_replace(' ', '_', $roleStat['name'])) . '_users';
            $stats[$roleKey] = $roleStat['count'];
        }

        // Ensure all expected role stats exist
        $expectedRoles = ['admin_users', 'moderator_users', 'member_users', 'sponsor_users', 'board_member_users'];
        foreach ($expectedRoles as $role) {
            if (!isset($stats[$role])) {
                $stats[$role] = 0;
            }
        }

        // Total sponsors
        $stats['total_sponsors'] = $stats['sponsor_users'];

        // Active sponsors (sponsors who have logged in recently)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT u.id) as count
            FROM users u
            JOIN roles r ON u.role_id = r.id
            JOIN sessions s ON u.id = s.user_id
            WHERE r.name = 'sponsor' AND s.last_activity > $dateFunction
        ");
        $stmt->execute();
        $stats['active_sponsors'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Notification and request stats
        // $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM notifications");
        // $stats['total_notifications'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE status = 'pending'");
        // $stmt->execute();
        // $stats['pending_notifications'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // // Monthly notifications
        // $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE created_at >= $monthStartDate");
        // $stmt->execute();
        // $stats['monthly_notifications'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Email/SMS breakdown (if available)
        $stats['email_notifications'] = 0;
        $stats['sms_notifications'] = 0;

        // Requests/support stats
        // $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM support_requests WHERE status = 'pending'");
        // $result = $stmt->fetch(PDO::FETCH_ASSOC);
        // $stats['pending_requests'] = $result['count'] ?? 0;

        // $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM support_requests WHERE status = 'resolved'");
        // $result = $stmt->fetch(PDO::FETCH_ASSOC);
        // $stats['resolved_requests'] = $result['count'] ?? 0;

        // // Monthly requests
        // $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM support_requests WHERE created_at >= $monthStartDate");
        // $stmt->execute();
        // 
        $stats['monthly_requests']=0;

        // Content stats (pages, images, documents)
       
        return $stats;
    }

    /**
     * Get recent activity for dashboard display
     * 
     * Aggregates recent user registrations, donations, and events for a summary view.
     * Returns an array of activity records, each with type, icon, title, and metadata.
     * 
     * @return array Recent activities (up to 5 items)
     */
    public function getRecentActivity()
    {
        $activities = [];

        // Recent user registrations
        $stmt = $this->pdo->query("SELECT id, email, created_at FROM users ORDER BY created_at DESC LIMIT 3");
        $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recentUsers as $user) {
            $activities[] = [
                'type' => 'user',
                'icon' => 'user-plus',
                'title' => 'New user registered',
                'meta' => htmlspecialchars($user['email']) . ' - ' . date('M d, H:i', strtotime($user['created_at']))
            ];
        }

        // Recent donations (completed payments)
        $stmt = $this->pdo->query("SELECT p.amount, u.email, p.created_at FROM payments p LEFT JOIN users u ON p.payer_user_id = u.id WHERE p.status = 'completed' ORDER BY p.created_at DESC LIMIT 2");
        $recentDonations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recentDonations as $donation) {
            $activities[] = [
                'type' => 'payment',
                'icon' => 'dollar-sign',
                'title' => 'Donation received',
                'meta' => '$' . number_format($donation['amount'], 2) . ' from ' . htmlspecialchars($donation['email'] ?? 'Anonymous') . ' - ' . date('M d, H:i', strtotime($donation['created_at']))
            ];
        }

        // Recent events
        $stmt = $this->pdo->query("SELECT title, start_at FROM events ORDER BY start_at DESC LIMIT 2");
        $recentEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recentEvents as $event) {
            $activities[] = [
                'type' => 'event',
                'icon' => 'calendar-alt',
                'title' => 'Event created',
                'meta' => htmlspecialchars($event['title']) . ' - ' . date('M d, Y', strtotime($event['start_at']))
            ];
        }

        // Sort activities by date and return up to 5
        return array_slice($activities, 0, 5);
    }

    /**
     * Get users with family data attached
     * 
     * Retrieves all users and their associated family members, with family size calculations.
     * Useful for both admin dashboards and member listings.
     * 
     * @return array Users with family_members and family_size keys
     */
    public function getUsersWithFamilyData()
    {
        $users = $this->getUsers();
        foreach ($users as &$user) {
            $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, birth_year, relationship, village, mosal, gender, phone_e164, email, occupation, business_info FROM family_members WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $familyMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $user['family_members'] = $familyMembers ?? [];
            $user['family_size'] = count($familyMembers);
        }
        return $users;
    }

    /**
     * Get active users (logged in within last 30 days)
     * 
     * @return array Active users with family data
     */
    public function getActiveUsers()
    {
    $dateFunction = "DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->pdo->prepare("
            SELECT u.*, r.name as role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            JOIN sessions s ON u.id = s.user_id
            WHERE s.last_activity > $dateFunction
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as &$user) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM family_members WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $familyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $user['family_size'] = 1 + $familyCount;
            
            $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, birth_year FROM family_members WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $user['family_members'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $users;
    }

    /**
     * Get all users
     * 
     * @return array All users with role information
     */
    public function getUsers()
    {
        $stmt = $this->pdo->query("
            SELECT u.*, r.name as role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            ORDER BY u.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user statistics for a specific user (for personal dashboard)
     * 
     * Returns personalized stats like profile completeness, family members, 
     * events registered, donations made, etc.
     * 
     * @param int $userId The user's ID
     * @return array User-specific statistics
     */
    public function getUserStats($userId)
    {
        $stats = [];

        // User profile info
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['user'] = $user;

        // Family size
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM family_members WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['family_size'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Events registered
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['events_registered'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Donations made
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM payments WHERE payer_user_id = ? AND status = 'completed'");
        $stmt->execute([$userId]);
        $stats['donations_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total donated
        $stmt = $this->pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE payer_user_id = ? AND status = 'completed'");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_donated'] = $result['total'] ?? 0;

        return $stats;
    }
}
