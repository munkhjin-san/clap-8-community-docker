<?php

// app/Domain/Contracts/PlanProvider.php
namespace App\Domain\Contracts;
use Carbon\CarbonImmutable;

interface PlanProvider {
  /** @return array<string,array{sales:?float,expense:?float,profit:?float}> keyed by project_code */
  public function fetchMonthlyPlans(CarbonImmutable $period, array $projectNames = []): array;
}


