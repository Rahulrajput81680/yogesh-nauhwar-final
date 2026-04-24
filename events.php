<?php require_once __DIR__ . '/components/frontend-init.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo frontend_escape(frontend_current_lang()); ?>">

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
	<title>Events & Public Programmes | Yogesh Nauhwar | RLD Mathura</title>
	<meta name="description"
		content="Stay updated with upcoming events, public meetings, grievance camps and development programmes of Chaudhary Yogesh Nauhwar, MLC UP, across Mathura and Mant region.">
	<link rel="canonical" href="https://yogeshnauhwar.com/events.php">
	<?php include 'components/links.php'; ?>
</head>

<body class="inner-page">
	<?php include 'components/loader.php'; ?>
	<?php include 'components/header.php'; ?>

	<?php
	$events = [];
	$page = max(1, (int) ($_GET['page'] ?? 1));
	$perPage = 9;
	$offset = ($page - 1) * $perPage;
	$typeFilter = frontend_sanitize_input($_GET['event_type'] ?? 'all');
	if (!in_array($typeFilter, ['all', 'upcoming', 'past'], true)) {
		$typeFilter = 'all';
	}

	try {
		$pdo = frontend_db();
		$where = "status = 'active'";
		$params = [];
		if ($typeFilter !== 'all') {
			$where .= ' AND event_type = ?';
			$params[] = $typeFilter;
		}

		$total = frontend_count_records($pdo, 'events', $where, $params);
		$totalPages = max(1, (int) ceil($total / $perPage));

		$stmt = $pdo->prepare("SELECT * FROM events WHERE {$where} ORDER BY COALESCE(event_date, created_at) DESC, id DESC LIMIT {$perPage} OFFSET {$offset}");
		$stmt->execute($params);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach ($rows as $row) {
			$events[] = [
				'image' => !empty($row['image']) ? frontend_upload_url($row['image']) : 'assets/img/events/event1.webp',
				'category' => $row['category'] ?: 'General',
				'title' => $row['title'] ?? '',
				'description' => $row['description'] ?? '',
				'status' => $row['event_type'] ?? 'upcoming',
			];
		}
	} catch (Throwable $e) {
		echo '<!-- Events Error: ' . htmlspecialchars($e->getMessage()) . ' -->';
		$events = [];
		$totalPages = 1;
	}
	?>

	<main>
		<section class="breadcrumb-section breadcrumb-section-events">
			<div class="container-fluid">
				<div class="row g-0">
					<div class="col-xl-12 col-lg-12">
						<div class="breadcrumb-content">
							<div class="breadcrumb-nav" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
								<ul>
									<li><a href='index.php'>Home</a></li>
									<li><a href="#">Events</a></li>
								</ul>
							</div>
							<div class="breadcrumb-title" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="400">
								<h2>Events</h2>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>

		<section class="services-section p-t-100 p-b-120 p-t-xs-80 p-b-xs-80">
			<div class="container">
				<div class="row justify-content-center text-center m-b-40">
					<div class="col-xl-8">
						<div class="common-subtitle" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
							<img alt="icon-1" src="assets/img/icons/wheat.png" class="wheat-icon"> <span>Events and Activities</span>
						</div>
						<div class="common-title m-b-0" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="400">
							<h2>From Villages to Vidhan Parishad</h2>
						</div>
					</div>
				</div>

				<div class="event-filter-wrap text-center m-b-40" data-aos="fade-up" data-aos-duration="1000"
					data-aos-delay="500">
					<a class="event-filter-btn<?php echo $typeFilter === 'all' ? ' active' : ''; ?>" href="events.php">All Events</a>
					<a class="event-filter-btn<?php echo $typeFilter === 'upcoming' ? ' active' : ''; ?>" href="events.php?event_type=upcoming">Upcoming Events</a>
					<a class="event-filter-btn<?php echo $typeFilter === 'past' ? ' active' : ''; ?>" href="events.php?event_type=past">Past Events</a>
				</div>

				<div class="row" id="eventsGrid" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="600">
					<?php if (empty($events)): ?>
						<div class="col-12 text-center">
							<p>No events available right now.</p>
						</div>
					<?php else: ?>
						<?php foreach ($events as $event): ?>
							<div class="col-xl-4 col-md-6 m-b-30 event-card-item"
								data-event-type="<?php echo frontend_escape($event['status']); ?>">
								<div class="camping-card">
									<div class="thumb">
										<img alt="event-thumb" src="<?php echo frontend_escape($event['image']); ?>">
										<div class="category">
											<a><?php echo frontend_escape($event['category']); ?></a>
										</div>
									</div>
									<div class="content">
										<div class="content-top">
											<div class="title">
												<h3><a><?php echo ($event['title']); ?></a></h3>
											</div>
											<div class="text">
												<p><?php echo ($event['description']); ?></p>
											</div>
										</div>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<div class="row justify-content-center text-center m-t-20">
					<div class="col-xl-6">
						<?php echo frontend_render_pagination($page, $totalPages, 'events.php', ['event_type' => $typeFilter !== 'all' ? $typeFilter : null]); ?>
					</div>
				</div>
			</div>
		</section>
	</main>

	<?php include 'components/footer.php'; ?>
	<?php include 'components/script.php'; ?>
</body>

</html>