<?php
/**
 * Realistic SEO Dashboard with Real Google Rankings
 * Shows actual positions, provides actionable improvements
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check admin authentication
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();

// Get real ranking statistics
$stats = [
    'total_tracked' => $db->fetchOne("SELECT COUNT(*) as count FROM seo_rankings WHERE is_real_data = 1")['count'] ?? 0,
    'rank_1' => $db->fetchOne("SELECT COUNT(*) as count FROM seo_rankings WHERE position = 1 AND is_real_data = 1")['count'] ?? 0,
    'top_3' => $db->fetchOne("SELECT COUNT(*) as count FROM seo_rankings WHERE position BETWEEN 1 AND 3 AND is_real_data = 1")['count'] ?? 0,
    'page_1' => $db->fetchOne("SELECT COUNT(*) as count FROM seo_rankings WHERE position BETWEEN 1 AND 10 AND is_real_data = 1")['count'] ?? 0,
    'page_2' => $db->fetchOne("SELECT COUNT(*) as count FROM seo_rankings WHERE position BETWEEN 11 AND 20 AND is_real_data = 1")['count'] ?? 0,
    'beyond_page_2' => $db->fetchOne("SELECT COUNT(*) as count FROM seo_rankings WHERE position > 20 AND is_real_data = 1")['count'] ?? 0,
    'not_found' => $db->fetchOne("SELECT COUNT(*) as count FROM seo_rankings WHERE position = 0 AND is_real_data = 1")['count'] ?? 0,
    'avg_position' => round($db->fetchOne("SELECT AVG(position) as avg FROM seo_rankings WHERE position > 0 AND is_real_data = 1")['avg'] ?? 0, 1)
];

// Get recent rankings
$recent_rankings = $db->fetchAll(
    "SELECT *, 
     CASE 
        WHEN position = 0 THEN 'Not Found'
        WHEN position BETWEEN 1 AND 3 THEN 'Excellent'
        WHEN position BETWEEN 4 AND 10 THEN 'Good'
        WHEN position BETWEEN 11 AND 20 THEN 'Needs Work'
        ELSE 'Poor'
     END as status_label,
     CASE 
        WHEN position = 0 THEN '#6c757d'
        WHEN position BETWEEN 1 AND 3 THEN '#28a745'
        WHEN position BETWEEN 4 AND 10 THEN '#17a2b8'
        WHEN position BETWEEN 11 AND 20 THEN '#ffc107'
        ELSE '#dc3545'
     END as status_color
     FROM seo_rankings 
     WHERE is_real_data = 1
     ORDER BY last_updated DESC 
     LIMIT 50"
);

// Generate keyword suggestions based on services and areas
$keyword_suggestions = [];
$services = ['Pigeon Nets', 'Safety Nets', 'Bird Nets', 'Cricket Nets', 'Invisible Grills', 'Balcony Nets'];
$areas = ['Kukatpally', 'Gachibowli', 'Hitech City', 'Madhapur', 'Kondapur', 'Miyapur'];

foreach ($services as $service) {
    foreach ($areas as $area) {
        $keyword_suggestions[] = [
            'keyword' => "{$service} in {$area}",
            'page_url' => '/' . strtolower(str_replace(' ', '-', $service)) . '-in-' . strtolower(str_replace(' ', '-', $area)) . '.php'
        ];
    }
}

$page_title = 'Advanced SEO Dashboard';
include '../includes/header.php';
?>
<style>
@keyframes spin{from{transform:rotate(0deg);}to{transform:rotate(360deg);}}
</style>

<!-- Hero -->
<div style="background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.07);padding:28px 32px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;border-left:6px solid #667eea;">
    <div>
        <h1 style="font-size:26px;font-weight:800;color:#1e293b;margin:0 0 4px;"><i class="fas fa-chart-line" style="color:#667eea;margin-right:8px;"></i>Advanced SEO Dashboard</h1>
        <p style="color:#64748b;font-size:14px;margin:0;">Real Google rankings with actionable improvement steps — data from <code>seo_rankings</code> table</p>
    </div>
    <div style="background:#f5f3ff;border:1.5px solid #ddd6fe;border-radius:12px;padding:12px 18px;text-align:center;flex-shrink:0;">
        <div style="font-size:28px;font-weight:800;color:#7c3aed;"><?php echo $stats['page_1']; ?></div>
        <div style="font-size:11px;color:#6d28d9;font-weight:600;">PAGE 1 RANKINGS</div>
    </div>
</div>

<?php if ($stats['total_tracked'] === 0): ?>
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-left:5px solid #3b82f6;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#1e40af;">
    <strong style="display:block;margin-bottom:4px;"><i class="fas fa-info-circle"></i> No Rankings Tracked Yet</strong>
    Start by checking your keyword positions below. This will show REAL Google search positions, not simulated data.
</div>
<?php endif; ?>

<!-- Primary Stats -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);overflow:hidden;margin-bottom:16px;">
    <div style="padding:22px 20px;text-align:center;position:relative;">
        <div style="font-size:34px;font-weight:800;color:#667eea;"><?php echo $stats['total_tracked']; ?></div>
        <div style="font-size:12px;color:#64748b;font-weight:600;margin-top:4px;"><i class="fas fa-search" style="color:#667eea;"></i> Keywords Tracked</div>
    </div>
    <div style="padding:22px 20px;text-align:center;position:relative;border-left:1px solid #e2e8f0;">
        <div style="font-size:34px;font-weight:800;color:#10b981;"><?php echo $stats['rank_1']; ?></div>
        <div style="font-size:12px;color:#64748b;font-weight:600;margin-top:4px;"><i class="fas fa-trophy" style="color:#10b981;"></i> Position #1</div>
    </div>
    <div style="padding:22px 20px;text-align:center;position:relative;border-left:1px solid #e2e8f0;">
        <div style="font-size:34px;font-weight:800;color:#3b82f6;"><?php echo $stats['page_1']; ?></div>
        <div style="font-size:12px;color:#64748b;font-weight:600;margin-top:4px;"><i class="fas fa-star" style="color:#3b82f6;"></i> Page 1 (1-10)</div>
    </div>
    <div style="padding:22px 20px;text-align:center;position:relative;border-left:1px solid #e2e8f0;">
        <div style="font-size:34px;font-weight:800;color:#1e293b;"><?php echo $stats['avg_position'] > 0 ? $stats['avg_position'] : 'N/A'; ?></div>
        <div style="font-size:12px;color:#64748b;font-weight:600;margin-top:4px;"><i class="fas fa-chart-bar"></i> Avg. Position</div>
    </div>
</div>

<!-- Secondary Stats -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);overflow:hidden;margin-bottom:20px;">
    <div style="padding:18px 20px;text-align:center;position:relative;">
        <div style="font-size:28px;font-weight:800;color:#10b981;"><?php echo $stats['top_3']; ?></div>
        <div style="font-size:12px;color:#64748b;font-weight:600;margin-top:4px;"><i class="fas fa-medal" style="color:#10b981;"></i> Top 3 Positions</div>
    </div>
    <div style="padding:18px 20px;text-align:center;position:relative;border-left:1px solid #e2e8f0;">
        <div style="font-size:28px;font-weight:800;color:#f59e0b;"><?php echo $stats['page_2']; ?></div>
        <div style="font-size:12px;color:#64748b;font-weight:600;margin-top:4px;"><i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i> Page 2 (11-20)</div>
    </div>
    <div style="padding:18px 20px;text-align:center;position:relative;border-left:1px solid #e2e8f0;">
        <div style="font-size:28px;font-weight:800;color:#ef4444;"><?php echo $stats['beyond_page_2']; ?></div>
        <div style="font-size:12px;color:#64748b;font-weight:600;margin-top:4px;"><i class="fas fa-arrow-down" style="color:#ef4444;"></i> Beyond Page 2</div>
    </div>
    <div style="padding:18px 20px;text-align:center;position:relative;border-left:1px solid #e2e8f0;">
        <div style="font-size:28px;font-weight:800;color:#ef4444;"><?php echo $stats['not_found']; ?></div>
        <div style="font-size:12px;color:#64748b;font-weight:600;margin-top:4px;"><i class="fas fa-times-circle" style="color:#ef4444;"></i> Not Found</div>
    </div>
</div>

<!-- Rank Checker -->
<div style="background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:20px;overflow:hidden;">
    <div style="padding:16px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px;">
        <h2 style="font-size:16px;font-weight:800;color:#1e293b;margin:0;"><i class="fas fa-search-location" style="color:#667eea;margin-right:8px;"></i>Check Real Google Position</h2>
    </div>
    <div style="padding:20px 24px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">Keyword to Check</label>
                <input type="text" id="keyword" style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;" placeholder="e.g., Pigeon Nets in Kukatpally">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">Page URL (optional)</label>
                <input type="text" id="page_url" style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;box-sizing:border-box;" placeholder="e.g., /pigeon-nets-in-kukatpally.php">
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button onclick="checkRanking()" style="padding:10px 20px;background:#10b981;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
                <i class="fas fa-search"></i> Check Position Now
            </button>
            <button onclick="showBulkChecker()" style="padding:10px 20px;background:#f1f5f9;color:#475569;border:none;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
                <i class="fas fa-list"></i> Bulk Check
            </button>
        </div>
        <div id="checkResult" style="margin-top:16px;"></div>
    </div>
</div>

<!-- Current Rankings -->
<div style="background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:20px;overflow:hidden;">
    <div style="padding:16px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px;">
        <h2 style="font-size:16px;font-weight:800;color:#1e293b;margin:0;flex:1;"><i class="fas fa-list-ol" style="color:#667eea;margin-right:8px;"></i>Current Rankings (Real Data)</h2>
        <span style="font-size:12px;color:#94a3b8;"><?php echo count($recent_rankings); ?> tracked</span>
    </div>
    <div style="padding:0;">
        <?php if (empty($recent_rankings)): ?>
        <div style="text-align:center;padding:40px;color:#94a3b8;">
            <i class="fas fa-search" style="font-size:32px;display:block;margin-bottom:10px;"></i>
            <p>No rankings tracked yet. Use the checker above to start tracking your positions.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr>
                    <th style="background:#f8fafc;padding:10px 14px;text-align:left;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;font-size:12px;">Keyword</th>
                    <th style="background:#f8fafc;padding:10px 14px;text-align:left;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;font-size:12px;">Page URL</th>
                    <th style="background:#f8fafc;padding:10px 14px;text-align:left;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;font-size:12px;">Position</th>
                    <th style="background:#f8fafc;padding:10px 14px;text-align:left;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;font-size:12px;">Search Page</th>
                    <th style="background:#f8fafc;padding:10px 14px;text-align:left;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;font-size:12px;">Status</th>
                    <th style="background:#f8fafc;padding:10px 14px;text-align:left;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;font-size:12px;">Last Checked</th>
                    <th style="background:#f8fafc;padding:10px 14px;text-align:left;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;font-size:12px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_rankings as $rank): ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 14px;"><strong><?php echo htmlspecialchars($rank['keyword']); ?></strong></td>
                    <td style="padding:10px 14px;"><code style="font-size:11px;"><?php echo htmlspecialchars($rank['page_url']); ?></code></td>
                    <td style="padding:10px 14px;"><span style="display:inline-block;padding:5px 12px;border-radius:20px;font-size:13px;font-weight:700;background:<?php echo $rank['status_color']; ?>;color:white;"><?php echo $rank['position'] > 0 ? '#'.$rank['position'] : 'Not Found'; ?></span></td>
                    <td style="padding:10px 14px;"><?php echo $rank['position'] > 0 ? 'Page '.ceil($rank['position']/10) : 'N/A'; ?></td>
                    <td style="padding:10px 14px;"><span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?php echo $rank['status_color']; ?>;color:white;"><?php echo $rank['status_label']; ?></span></td>
                    <td style="padding:10px 14px;font-size:12px;color:#64748b;"><?php echo date('d M Y, h:i A', strtotime($rank['last_updated'])); ?></td>
                    <td style="padding:10px 14px;"><button onclick="recheckKeyword('<?php echo htmlspecialchars($rank['keyword']); ?>','<?php echo htmlspecialchars($rank['page_url']); ?>')" style="padding:6px 12px;background:#667eea;color:white;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-sync"></i> Recheck</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- How to Rank #1 -->
<div style="background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:20px;overflow:hidden;">
    <div style="padding:16px 22px;border-bottom:1px solid #f1f5f9;">
        <h2 style="font-size:16px;font-weight:800;color:#1e293b;margin:0;"><i class="fas fa-rocket" style="color:#667eea;margin-right:8px;"></i>How to Reach #1 Position on Google</h2>
    </div>
    <div style="padding:20px 24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">
        <div style="background:#eff6ff;border-left:4px solid #3b82f6;border-radius:10px;padding:16px 18px;">
            <div style="font-weight:700;color:#1e40af;margin-bottom:10px;"><i class="fas fa-pen-nib" style="margin-right:6px;"></i>Content Optimization</div>
            <ol style="margin-left:18px;color:#1e40af;font-size:13px;">
                <li style="margin-bottom:5px;">Write 1500+ words of high-quality content</li>
                <li style="margin-bottom:5px;">Use keyword in title, H1, first paragraph</li>
                <li style="margin-bottom:5px;">Add keyword variations naturally (LSI keywords)</li>
                <li style="margin-bottom:5px;">Include images with alt text</li>
                <li style="margin-bottom:5px;">Add videos if possible</li>
                <li>Update content monthly</li>
            </ol>
        </div>
        <div style="background:#f0fdf4;border-left:4px solid #10b981;border-radius:10px;padding:16px 18px;">
            <div style="font-weight:700;color:#065f46;margin-bottom:10px;"><i class="fas fa-link" style="margin-right:6px;"></i>Link Building</div>
            <ol style="margin-left:18px;color:#065f46;font-size:13px;">
                <li style="margin-bottom:5px;">Get 10+ quality backlinks from relevant sites</li>
                <li style="margin-bottom:5px;">Build internal links from other pages</li>
                <li style="margin-bottom:5px;">Guest post on industry blogs</li>
                <li style="margin-bottom:5px;">Get listed in local directories</li>
                <li style="margin-bottom:5px;">Create shareable infographics</li>
                <li>Reach out to industry influencers</li>
            </ol>
        </div>
        <div style="background:#fffbeb;border-left:4px solid #f59e0b;border-radius:10px;padding:16px 18px;">
            <div style="font-weight:700;color:#92400e;margin-bottom:10px;"><i class="fas fa-bolt" style="margin-right:6px;"></i>Technical SEO</div>
            <ol style="margin-left:18px;color:#92400e;font-size:13px;">
                <li style="margin-bottom:5px;">Improve page load speed (&lt;3 seconds)</li>
                <li style="margin-bottom:5px;">Make site mobile-friendly</li>
                <li style="margin-bottom:5px;">Add schema markup</li>
                <li style="margin-bottom:5px;">Fix broken links</li>
                <li style="margin-bottom:5px;">Optimize images (compress, WebP)</li>
                <li>Enable HTTPS</li>
            </ol>
        </div>
        <div style="background:#f5f3ff;border-left:4px solid #8b5cf6;border-radius:10px;padding:16px 18px;">
            <div style="font-weight:700;color:#4c1d95;margin-bottom:10px;"><i class="fas fa-users" style="margin-right:6px;"></i>User Experience</div>
            <ol style="margin-left:18px;color:#4c1d95;font-size:13px;">
                <li style="margin-bottom:5px;">Reduce bounce rate (&lt;40%)</li>
                <li style="margin-bottom:5px;">Increase time on page (&gt;2 minutes)</li>
                <li style="margin-bottom:5px;">Add clear call-to-actions</li>
                <li style="margin-bottom:5px;">Improve readability (short paragraphs)</li>
                <li style="margin-bottom:5px;">Add customer reviews</li>
                <li>Make contact info prominent</li>
            </ol>
        </div>
    </div>
</div>
    
    <script>
        function checkRanking() {
            const keyword = document.getElementById('keyword').value.trim();
            const page_url = document.getElementById('page_url').value.trim();
            const resultDiv = document.getElementById('checkResult');
            
            if (!keyword) {
                alert('Please enter a keyword');
                return;
            }
            
            resultDiv.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i><br>Checking Google position... This may take 30-60 seconds...</div>';
            
            fetch('../api/real-time-rank-checker.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=check_single&keyword=${encodeURIComponent(keyword)}&page_url=${encodeURIComponent(page_url)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const position = data.position;
                    const color = position === 0 ? '#6c757d' : position <= 3 ? '#28a745' : position <= 10 ? '#17a2b8' : position <= 20 ? '#ffc107' : '#dc3545';
                    
                    let html = `
                        <div style="background: white; padding: 30px; border-radius: 12px; border-left: 4px solid ${color};">
                            <h3 style="color: ${color}; margin-bottom: 20px;">
                                <i class="fas fa-chart-line"></i> Position: ${position > 0 ? '#' + position : 'Not Found'}
                            </h3>
                            <p><strong>Keyword:</strong> ${data.keyword}</p>
                            <p><strong>Search Page:</strong> ${data.search_page > 0 ? 'Page ' + data.search_page : 'Not in top 100'}</p>
                            <p><strong>Message:</strong> ${data.message}</p>
                    `;
                    
                    if (data.recommendations) {
                        html += `
                            <div class="recommendations" style="margin-top: 20px;">
                                <h4>${data.recommendations.message}</h4>
                                <p><strong>Status:</strong> ${data.recommendations.status.toUpperCase()}</p>
                                <h5 style="margin-top: 15px;">Action Steps:</h5>
                                <ol>
                        `;
                        
                        data.recommendations.actions.forEach(action => {
                            html += `<li>${action}</li>`;
                        });
                        
                        html += `
                                </ol>
                            </div>
                        `;
                    }
                    
                    html += '</div>';
                    resultDiv.innerHTML = html;
                    
                    // Reload page after 3 seconds to show updated stats
                    setTimeout(() => location.reload(), 3000);
                } else {
                    resultDiv.innerHTML = `<div class="alert" style="background: #f8d7da; border-color: #dc3545; color: #721c24;">${data.message}</div>`;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="alert" style="background: #f8d7da; border-color: #dc3545; color: #721c24;">Error: ${error.message}</div>`;
            });
        }
        
        function recheckKeyword(keyword, page_url) {
            document.getElementById('keyword').value = keyword;
            document.getElementById('page_url').value = page_url;
            checkRanking();
        }
        
        function showBulkChecker() {
            alert('Bulk checker feature: Check multiple keywords at once. This feature will be available in the next update.');
        }
    </script>

<?php include '../includes/footer.php'; ?>
