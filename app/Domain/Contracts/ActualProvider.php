<?php

// app/Domain/Contracts/ActualProvider.php
namespace App\Domain\Contracts;
use Carbon\CarbonImmutable;

interface ActualProvider {
  /** same shape, keyed by project_code */
  public function fetchMonthlyActuals(CarbonImmutable $period, array $projectNames = []): array;
}