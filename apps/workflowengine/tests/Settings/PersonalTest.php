<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowEngine\Tests\Settings;

use OCA\WorkflowEngine\Manager;
use OCA\WorkflowEngine\Settings\Personal;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IOperation;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PersonalTest extends TestCase {
	private Manager&MockObject $manager;
	private IInitialState&MockObject $initialState;
	private Personal $settings;

	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(Manager::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->settings = new Personal(
			$this->createMock(IEventDispatcher::class),
			$this->manager,
			$this->initialState,
			$this->createMock(IConfig::class),
			$this->createMock(IURLGenerator::class),
		);
	}

	public function testGetSectionWithoutUserScope(): void {
		$this->manager->method('isUserScopeEnabled')->willReturn(false);
		$this->manager->expects($this->never())->method('getOperatorList');

		$this->assertNull($this->settings->getSection());
	}

	public function testGetSectionWithoutAvailableOperator(): void {
		$this->manager->method('isUserScopeEnabled')->willReturn(true);
		$this->manager->method('getOperatorList')->willReturn([
			$this->getOperator(IManager::SCOPE_ADMIN),
		]);

		$this->assertNull($this->settings->getSection());
	}

	public function testGetSectionWithAvailableOperator(): void {
		$this->manager->method('isUserScopeEnabled')->willReturn(true);
		$this->manager->method('getOperatorList')->willReturn([
			$this->getOperator(IManager::SCOPE_ADMIN),
			$this->getOperator(IManager::SCOPE_USER),
		]);

		$this->assertSame('workflow', $this->settings->getSection());
	}

	public function testGetFormOnlyOffersOperatorsOfTheUserScope(): void {
		$userOperator = $this->getOperator(IManager::SCOPE_USER);
		$this->manager->method('isUserScopeEnabled')->willReturn(true);
		$this->manager->method('getEntitiesList')->willReturn([]);
		$this->manager->method('getCheckList')->willReturn([]);
		$this->manager->method('getOperatorList')->willReturn([
			$this->getOperator(IManager::SCOPE_ADMIN),
			$userOperator,
		]);

		$providedStates = [];
		$this->initialState->method('provideInitialState')
			->willReturnCallback(function (string $key, mixed $value) use (&$providedStates): void {
				$providedStates[$key] = $value;
			});

		$this->settings->getForm();

		$this->assertSame([get_class($userOperator)], array_column($providedStates['operators'], 'id'));
	}

	private function getOperator(int $availableScope): IOperation&MockObject {
		$operator = $this->createMock(IOperation::class);
		$operator->method('isAvailableForScope')
			->willReturnCallback(fn (int $scope): bool => $scope === $availableScope);

		return $operator;
	}
}
