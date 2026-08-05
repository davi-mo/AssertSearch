<?php

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit/Services');

pest()->extend(TestCase::class)
    ->in('Unit/Indexing');

pest()->extend(TestCase::class)
    ->in('Unit/Search');
