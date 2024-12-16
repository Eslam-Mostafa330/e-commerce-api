<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\ProductsController;
use App\Http\Controllers\Api\WishListController;
use Illuminate\Support\Facades\Route;


## ---- Auth Routes
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
});

## ------------- Admin Auth Routes
Route::controller(AdminAuthController::class)->group(function () {
    Route::post('/admin/register', 'register');
    Route::post('/admin/login', 'login');
    Route::post('/admin/logout', 'logout')->middleware('auth:sanctum');
});

## ------------- Wishlist Routes
Route::middleware(['auth:sanctum'])->controller(WishListController::class)->prefix('wishlist')->group(function () {
    Route::post('/toggle-wishlist', 'toggleWishlist');
    Route::get('/view-wishlist', 'index');
    Route::post('/remove-from-wishlist', 'removeFromWishlist');
});

## ------------- Cart Routes (Guest Users)
Route::controller(CartController::class)->prefix('guest-cart')->group(function () {
    Route::get('/view-items', 'index');
    Route::post('/add', 'addToCart');
    Route::post('/remove', 'removeFromCart');
    Route::get('/total-price', 'calculateTotalPrice');
});

## ------------- Cart Routes (Auth Users)
Route::middleware('auth:sanctum')->controller(CartController::class)->prefix('auth-cart')->group(function () {
    Route::get('/view-items', 'index');
    Route::post('/add', 'addToCart');
    Route::post('/remove', 'removeFromCart');
    Route::get('/total-price', 'calculateTotalPrice');
});

## ------------- Admin Routes
Route::middleware(['protectAdminRoutes', 'auth:sanctum'])->prefix('admin')->group(function () {
    ## ----- Categories Routes
    Route::controller(CategoriesController::class)->group(function () {
        Route::get('/categories', 'index');
        Route::get('/category', 'show');
        Route::post('/categories/store', 'store');
        Route::post('/category/edit/{id}', 'update');
        Route::post('/category/destroy/{id}', 'destroy');
    });

    ## ----- Products Routes
    Route::controller(ProductsController::class)->group(function () {
        Route::get('/products', 'index');
        Route::get('/product/show/{id}', 'show');
        Route::post('/product/store', 'store');
        Route::post('/product/edit/{id}', 'update');
        Route::post('/product/destroy/{id}', 'destroy');
    });
});
