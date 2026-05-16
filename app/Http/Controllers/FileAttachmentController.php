<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;

class FileAttachmentController extends Controller
{
    public function index(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        $type = $request->query('type');
        $entityId = $request->query('entity_id');

        $sql = 'SELECT * FROM file_attachments WHERE company_id = :cid';
        $params = ['cid' => $companyId];

        if ($type) {
            $sql .= ' AND attachable_type = :type';
            $params['type'] = $type;
        }
        if ($entityId) {
            $sql .= ' AND attachable_id = :eid';
            $params['eid'] = (int) $entityId;
        }
        $sql .= ' ORDER BY created_at DESC LIMIT 100';

        $attachments = [];
        try {
            $stmt = $db->pdo()->prepare($sql);
            $stmt->execute($params);
            $attachments = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        // If AJAX/JSON requested, return JSON
        if ($request->wantsJson()) {
            return response()->json(['data' => $attachments]);
        }

        return view('attachments.index', [
            'company' => $company,
            'user' => $_SESSION['user'] ?? null,
            'attachments' => $attachments,
            'filterType' => $type,
            'filterEntityId' => $entityId,
        ]);
    }

    public function upload(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'attachable_type' => 'required|in:invoice,estimate,expense,client,recurring',
            'attachable_id' => 'required|integer',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $mime = $file->getMimeType();
        $size = $file->getSize();

        // Reject dangerous file types
        $blockedExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'exe', 'bat', 'sh', 'cmd', 'js', 'vbs'];
        $ext = strtolower($file->getClientOriginalExtension());
        if (in_array($ext, $blockedExtensions, true)) {
            return back()->withErrors(['file' => 'This file type is not allowed.']);
        }

        // Store in company-specific directory
        $directory = "attachments/{$companyId}/{$validated['attachable_type']}";
        $storedPath = $file->store($directory, 'local');

        if (!$storedPath) {
            return back()->withErrors(['file' => 'Could not store file.']);
        }

        $uploadedBy = (int) ($_SESSION['user']['user_id'] ?? 0) ?: null;

        try {
            $db->pdo()->prepare(
                'INSERT INTO file_attachments (company_id, attachable_type, attachable_id, original_name, stored_path, mime_type, size_bytes, uploaded_by, created_at, updated_at)
                 VALUES (:cid, :type, :eid, :name, :path, :mime, :size, :by, NOW(), NOW())'
            )->execute([
                'cid' => $companyId,
                'type' => $validated['attachable_type'],
                'eid' => (int) $validated['attachable_id'],
                'name' => $originalName,
                'path' => $storedPath,
                'mime' => $mime,
                'size' => $size,
                'by' => $uploadedBy,
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Could not save attachment record.']);
        }

        // Log activity
        try {
            ActivityFeed::log($db, $context, 'attached file', $validated['attachable_type'], (int) $validated['attachable_id'], $originalName);
        } catch (\Throwable $e) {
            // Non-blocking: activity logging should not break the upload
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'File uploaded', 'file' => $originalName], 201);
        }

        return back()->with('success', "File \"{$originalName}\" attached successfully.");
    }

    public function download($id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        $attachment = null;
        try {
            $stmt = $db->pdo()->prepare('SELECT * FROM file_attachments WHERE attachment_id = :id AND company_id = :cid LIMIT 1');
            $stmt->execute(['id' => (int) $id, 'cid' => $companyId]);
            $attachment = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            abort(404, 'Attachment not found.');
        }

        if (!$attachment) {
            abort(404, 'Attachment not found.');
        }

        $fullPath = storage_path('app/' . $attachment['stored_path']);
        if (!file_exists($fullPath)) {
            abort(404, 'File not found on disk.');
        }

        return response()->download($fullPath, $attachment['original_name']);
    }

    public function destroy($id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        $attachment = null;
        try {
            $stmt = $db->pdo()->prepare('SELECT * FROM file_attachments WHERE attachment_id = :id AND company_id = :cid LIMIT 1');
            $stmt->execute(['id' => (int) $id, 'cid' => $companyId]);
            $attachment = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Attachment not found.']);
        }

        if (!$attachment) {
            return back()->withErrors(['file' => 'Attachment not found.']);
        }

        // Delete from disk
        $fullPath = storage_path('app/' . $attachment['stored_path']);
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }

        // Delete DB record
        try {
            $db->pdo()->prepare('DELETE FROM file_attachments WHERE attachment_id = :id AND company_id = :cid')
                ->execute(['id' => (int) $id, 'cid' => $companyId]);
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Could not delete attachment.']);
        }

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Attachment deleted.']);
        }

        return back()->with('success', 'Attachment deleted.');
    }
}
