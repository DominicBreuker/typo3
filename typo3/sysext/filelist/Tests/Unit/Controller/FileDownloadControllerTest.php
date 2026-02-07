<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Filelist\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Filelist\Controller\FileDownloadController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileDownloadControllerTest extends UnitTestCase
{
    #[Test]
    public function collectFilesSkipsFilesFromFallbackStorage(): void
    {
        $fallbackStorageMock = $this->createMock(ResourceStorage::class);
        $fallbackStorageMock->method('isFallbackStorage')->willReturn(true);

        $fileMock = $this->createMock(File::class);
        $fileMock->method('getStorage')->willReturn($fallbackStorageMock);
        $fileMock->method('getIdentifier')->willReturn('/typo3conf/system/settings.php');
        $fileMock->method('getName')->willReturn('settings.php');

        $resourceFactoryMock = $this->createMock(ResourceFactory::class);
        $resourceFactoryMock->method('retrieveFileOrFolderObject')
            ->with('typo3conf/system/settings.php')
            ->willReturn($fileMock);

        $subject = new FileDownloadController(
            $resourceFactoryMock,
            $this->createMock(ResponseFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
            $this->createMock(Context::class),
        );

        $reflection = new \ReflectionMethod($subject, 'collectFiles');

        $result = $reflection->invoke($subject, ['typo3conf/system/settings.php']);
        self::assertSame([], $result);
    }

    #[Test]
    public function collectFilesIncludesFilesFromRegularStorage(): void
    {
        $regularStorageMock = $this->createMock(ResourceStorage::class);
        $regularStorageMock->method('isFallbackStorage')->willReturn(false);

        $fileMock = $this->createMock(File::class);
        $fileMock->method('getStorage')->willReturn($regularStorageMock);
        $fileMock->method('getIdentifier')->willReturn('/fileadmin/test.txt');
        $fileMock->method('getName')->willReturn('test.txt');

        $resourceFactoryMock = $this->createMock(ResourceFactory::class);
        $resourceFactoryMock->method('retrieveFileOrFolderObject')
            ->with('1:/fileadmin/test.txt')
            ->willReturn($fileMock);

        $subject = new FileDownloadController(
            $resourceFactoryMock,
            $this->createMock(ResponseFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
            $this->createMock(Context::class),
        );

        $reflection = new \ReflectionMethod($subject, 'collectFiles');

        $result = $reflection->invoke($subject, ['1:/fileadmin/test.txt']);
        self::assertCount(1, $result);
        self::assertSame($fileMock, $result['test.txt']);
    }
}
