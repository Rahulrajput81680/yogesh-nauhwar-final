<?php require_once __DIR__ . '/../components/frontend-init.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo frontend_escape(frontend_current_lang()); ?>">

<head>
	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title>Blog | Yogesh Nauhwar | Farmer Rights & UP Politics</title>
	<meta name="description"
		content="Read Chaudhary Yogesh Nauhwar's views on farmer welfare, rural development, Uttar Pradesh politics and social justice. Official blog of the RLD leader from Mathura.">
	<link rel="canonical" href="https://yogeshnauhwar.com/blogs/index.php">	
	<base href="../">
	<?php include '../components/links.php'; ?>
</head>

<body class="inner-page">
	<?php include '../components/loader.php'; ?>
	<?php include '../components/header.php'; ?>

	<?php
	$blogs = [];
	$page = max(1, (int) ($_GET['page'] ?? 1));
	$perPage = 9;
	$offset = ($page - 1) * $perPage;

	try {
		$pdo = frontend_db();
		$hasDeletedAt = frontend_has_column($pdo, 'blogs', 'deleted_at');
		$where = "status = 'published'";
		if ($hasDeletedAt) {
			$where .= ' AND deleted_at IS NULL';
		}

		$total = frontend_count_records($pdo, 'blogs', $where, []);
		$totalPages = max(1, (int) ceil($total / $perPage));

		$stmt = $pdo->prepare("SELECT id, title, slug, thumbnail, category, created_at FROM blogs WHERE {$where} ORDER BY created_at DESC, id DESC LIMIT {$perPage} OFFSET {$offset}");
		$stmt->execute();
		$blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch (Throwable $e) {
		$blogs = [];
		$totalPages = 1;
	}
	?>

	<main>
		<!-- breadcrumb-section start -->
		<section class="breadcrumb-section breadcrumb-section-blogs">
			<div class="container-fluid">
				<div class="row g-0">
					<div class="col-xl-12 col-lg-12">
						<div class="breadcrumb-content">
							<div class="breadcrumb-nav" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
								<ul>
									<li><a href='index.html'>Home</a></li>
									<li><a href="#">Blogs</a></li>
								</ul>
							</div>
							<div class="breadcrumb-title" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="400">
								<h2>Blogs</h2>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<!-- breadcrumb-section end -->

		<!-- volunteer-section start -->
		<section class="blog-section p-t-120 p-b-120 p-t-lg-80 p-b-lg-80 p-t-md-60 p-b-md-60 p-t-xs-60 p-b-xs-60">
			<div class="container">
				<div class="row" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="600">
					<?php if (empty($blogs)): ?>
						<div class="col-12 text-center">
							<p>No blog posts available right now.</p>
						</div>
					<?php else: ?>
						<?php foreach ($blogs as $blog): ?>
							<?php
							$thumb = !empty($blog['thumbnail']) ? frontend_upload_url($blog['thumbnail']) : 'assets/img/thumbs/thumb-32.webp';
							$timestamp = !empty($blog['created_at']) ? strtotime($blog['created_at']) : time();
							$detailUrl = 'blogs/blog-detail.php?slug=' . urlencode($blog['slug']);
							?>
							<div class="col-xl-4 col-md-6 col-sm-12 m-b-30">
								<div class="blog-card-2">
									<div class="thumb">
										<a href='<?php echo frontend_escape($detailUrl); ?>'>
											<img src="<?php echo frontend_escape($thumb); ?>" alt="thumb" />
										</a>
										<!-- <div class="event-date">
											<h2><?php echo date('d', $timestamp); ?></h2>
											<h5><?php echo date('M', $timestamp); ?></h5>
										</div> -->
									</div>
									<div class="content">
										<div class="content-top p-0 m-b-20">
											<div class="author">
												<div class="admin">
													<i class="fa-light fa-circle-user"></i>
													<span>Admin</span>
												</div>
												<!-- <div class="solar">
													<i class="fa-light fa-bookmark"></i>
													<span><?php echo ($blog['category'] ?: 'General'); ?></span>
												</div> -->
											</div>
											<div class="title">
												<h3>
													<a href='<?php echo ($detailUrl); ?>'>
														<?php echo ($blog['title']); ?>
													</a>
												</h3>
											</div>
										</div>
										<div class="content-bottom">
											<a class='e-primary-btn has-icon has-small read-more-btn'
												href='<?php echo ($detailUrl); ?>'>
												Read More
												<span class="icon-wrap"><span class="icon"><i class="fa-regular fa-arrow-right"></i><i
															class="fa-regular fa-arrow-right"></i></span></span>
											</a>
										</div>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<div class="row justify-content-center text-center m-t-20" data-aos="fade-up" data-aos-duration="1000"
					data-aos-delay="200">
					<div class="col-xl-6">
						<?php echo frontend_render_pagination($page, $totalPages, 'blogs/index.php'); ?>
					</div>
				</div>
			</div>
		</section>
		<!-- volunteer-section start -->

	</main>

	<?php include '../components/script.php'; ?>
	<?php include '../components/footer.php'; ?>
</body>

</html>