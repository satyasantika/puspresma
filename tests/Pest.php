<?php

use Tests\TestCase;

// Manifest Vite (public/build) tidak diperlukan oleh test.
pest()->extend(TestCase::class)->beforeEach(fn () => $this->withoutVite())->in('Feature');
pest()->extend(TestCase::class)->in('Arch');
pest()->extend(PHPUnit\Framework\TestCase::class)->in('Unit');
