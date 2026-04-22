<?php require_once __DIR__ . '/components/frontend-init.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo frontend_escape(frontend_current_lang()); ?>">

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
	<title>Our Work — Yogesh Nauhwar MLC | Development Works & Achievements Mant Mathura</title>
	<meta name="description"
		content="Explore the development works and achievements of Chaudhary Yogesh Nauhwar MLC — including ₹14.41 crore road projects, farmer welfare programs, youth skill development, and rural infrastructure works in Mant Mathura.">
	<?php include 'components/links.php'; ?>
</head>

<body class="inner-page">
	<?php include 'components/loader.php'; ?>
	<?php include 'components/header.php'; ?>

	<?php
	$galleryItems = [];
	$page = max(1, (int) ($_GET['page'] ?? 1));
	$perPage = 9;
	$offset = ($page - 1) * $perPage;
	$sortFilter = frontend_sanitize_input($_GET['sort'] ?? 'all');
	if (!in_array($sortFilter, ['all', 'latest', 'last_2_months', 'oldest'], true)) {
		$sortFilter = 'all';
	}

	try {
		$pdo = frontend_db();
		$total = frontend_gallery_count($pdo, 'our_work', ['date_filter' => $sortFilter]);
		$totalPages = max(1, (int) ceil($total / $perPage));
		$galleryItems = frontend_gallery_items($pdo, 'our_work', $perPage, $offset, [
			'sort' => $sortFilter,
			'date_filter' => $sortFilter,
		]);
	} catch (Throwable $e) {
		$galleryItems = [];
		$totalPages = 1;
	}
	?>

	<main>
		<section class="breadcrumb-section breadcrumb-section-our-work">
			<div class="container-fluid">
				<div class="row g-0">
					<div class="col-xl-12 col-lg-12">
						<div class="breadcrumb-content">
							<div class="breadcrumb-nav" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
								<ul>
									<li><a href='index.php'>Home</a></li>
									<li><a href="#">Our Work</a></li>
								</ul>
							</div>
							<div class="breadcrumb-title" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="400">
								<h2>Our Work</h2>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>

		<section class="services-section bg-cream p-t-100 p-b-70">
			<div class="container">
				<div class="row justify-content-center text-center m-b-50 m-b-xs-40">
					<div class="col-xl-6">
						<div class="common-subtitle" data-aos="fade-up" data-aos-delay="600" data-aos-duration="1000">
							<img alt="icon-1" src="assets/img/icons/wheat.png" class="wheat-icon"> <span>Our Work</span>
						</div>
						<div class="common-title m-b-0" data-aos="fade-up" data-aos-delay="800" data-aos-duration="1000">
							<h2>Real Work, Real Results</h2>
						</div>
					</div>
				</div>
				<div class="row justify-content-center m-b-40">
					<div class="col-xl-8 col-lg-8 col-md-8">
						<div class="event-filter-wrap text-center m-b-0" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="500">
							<a class="event-filter-btn<?php echo $sortFilter === 'all' ? ' active' : ''; ?>" href="our-work.php">All Work</a>
							<a class="event-filter-btn<?php echo $sortFilter === 'latest' ? ' active' : ''; ?>" href="our-work.php?sort=latest">Latest</a>
							<a class="event-filter-btn<?php echo $sortFilter === 'last_2_months' ? ' active' : ''; ?>" href="our-work.php?sort=last_2_months">Last 2 Months</a>
							<a class="event-filter-btn<?php echo $sortFilter === 'oldest' ? ' active' : ''; ?>" href="our-work.php?sort=oldest">Oldest</a>
						</div>
					</div>
				</div>
			</div>
			<div class="container">
				<div class="row equal-height-card-row" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
					<?php if (empty($galleryItems)): ?>
						<div class="col-12 text-center">
							<p>No work images available right now.</p>
						</div>
					<?php else: ?>
						<?php foreach ($galleryItems as $item): ?>
							<?php
							$thumb = !empty($item['image']) ? frontend_upload_url($item['image']) : 'assets/img/home/media-coverage/news7.webp';
							$category = $item['category'] ?: 'General';
							?>
							<div class="col-xl-4 col-md-6 m-b-30">
								<div class="project-card style-service">
									<div class="thumb">
										<a href="<?php echo frontend_escape($thumb); ?>"
											data-fancybox="our-work-gallery">
											<img alt="<?php echo frontend_escape($item['title'] ?: 'Work image'); ?>"
												src="<?php echo frontend_escape($thumb); ?>">
										</a>
									</div>
									<div class="content text-center p-t-20">
										<span class="badge bg-light text-dark"><?php echo frontend_escape($category); ?></span>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<div class="row justify-content-center text-center m-t-20">
					<div class="col-xl-6">
						<?php echo frontend_render_pagination($page, $totalPages, 'our-work.php', ['sort' => $sortFilter]); ?>
					</div>
				</div>
			</div>
			<!-- <div class="c-shape-1"><img alt="shape-30" src="assets/img/shapes/shape-30.webp"></div>
		<div class="c-shape-2"><img alt="shape-31" src="assets/img/shapes/shape-31.webp"></div> -->
		</section>
	</main>

	<?php include 'components/footer.php'; ?>
	<?php include 'components/script.php'; ?>
</body>

</html>