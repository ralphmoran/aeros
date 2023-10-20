<?php
namespace Classes;

abstract class ServiceProvider
{
    abstract public function register(): void;
    abstract public function boot(): void;
}
