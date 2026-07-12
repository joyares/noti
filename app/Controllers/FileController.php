<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\Attachment;
use App\Services\LockService;

final class FileController extends WebController
{
    /** GET /files/{id} — serve an attachment with ownership + lock checks. */
    public function show(string $id): void
    {
        $att = (new Attachment())->findWithOwner((int) $id);
        if ($att === null || (int) $att['user_id'] !== $this->userId()) {
            Response::notFound($this->request);
        }

        $note = (new \App\Models\Note())->findFull((int) $att['note_id'], $this->userId());
        if ($note !== null && (int) $note['notebook_locked'] === 1
            && !(new LockService())->isUnlockedWeb((int) $note['notebook_id'])) {
            http_response_code(423);
            echo 'Locked';
            exit;
        }

        $abs = BASE_PATH . '/' . $att['file_path'];
        if (!is_file($abs)) {
            Response::notFound($this->request);
        }

        header('Content-Type: ' . $att['mime']);
        header('Content-Length: ' . filesize($abs));
        header('X-Content-Type-Options: nosniff');
        $disposition = str_starts_with($att['mime'], 'image/') || str_starts_with($att['mime'], 'audio/')
            ? 'inline' : 'attachment';
        header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($att['file_name']) . '"');
        readfile($abs);
        exit;
    }
}
