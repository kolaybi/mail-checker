<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use KolayBi\Validation\Mail\Tests\SuppressionTestCase;
use KolayBi\Validation\Mail\Tests\TestCase;

uses(TestCase::class)->in(__DIR__ . '/*.php', __DIR__ . '/Console', __DIR__ . '/Providers');
uses(SuppressionTestCase::class, RefreshDatabase::class)->in(__DIR__ . '/Suppression');
