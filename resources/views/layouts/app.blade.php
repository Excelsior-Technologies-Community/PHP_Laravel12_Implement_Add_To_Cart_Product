<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shop - Laravel 12</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        .cart-badge {
            font-size: 0.85rem;
            vertical-align: top;
        }
        .alert {
            border-radius: 0.4rem;
        }
        footer {
            background: #343a40;
            color: #fff;
            padding: 30px 0;
            margin-top: 50px;
        }
        footer a {
            color: #fff;
            text-decoration: none;
        }
        footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand" href="{{ route('products.index') }}">Shop</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item me-3">
          <a class="nav-link position-relative" href="{{ route('cart.index') }}">
            <i class="bi bi-cart-fill fs-5"></i>
            <span class="badge bg-primary rounded-pill cart-badge" id="cart-count">
              {{ array_sum(array_column(session('cart', []), 'quantity') ?: [0]) }}
            </span>
          </a>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-primary" href="{{ route('products.index') }}">Shop Now</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Alerts -->
<div class="container">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
</div>

<!-- Main Content -->
<div class="container">
    @yield('content')
</div>


<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

@stack('scripts')
</body>
</html>
