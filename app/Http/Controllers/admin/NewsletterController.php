<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Services\NewsletterSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class NewsletterController extends Controller
{
    public function __construct()
    {
        // Gyros City has no FoodPRO module-permission registry; match its owner-only promotion tools.
        $this->middleware(function ($request, $next) {
            abort_unless($request->user() && (int) $request->user()->type === 1, 403);
            abort_unless(Schema::hasTable('newsletters') && Schema::hasTable('newsletter_deliveries'), 503, trans('newsletter.not_installed'));
            return $next($request);
        });
    }

    public function index(NewsletterSender $sender)
    {
        return view('admin.newsletter.index', ['letters' => Newsletter::orderByDesc('id')->paginate(15),
            'recipientCount' => $sender->recipientCount()]);
    }

    public function create(NewsletterSender $sender)
    {
        return view('admin.newsletter.edit', ['letter' => new Newsletter(), 'recipientCount' => $sender->recipientCount()]);
    }

    public function store(Request $request)
    {
        $letter = Newsletter::create($this->content($request) + ['created_by' => $request->user()->id]);
        return redirect()->route('admin.newsletter.edit', $letter)->with('success', trans('newsletter.saved'));
    }

    public function edit(Newsletter $newsletter, NewsletterSender $sender)
    {
        if ($newsletter->status !== 'draft') return redirect()->route('admin.newsletter.show', $newsletter);
        return view('admin.newsletter.edit', ['letter' => $newsletter, 'recipientCount' => $sender->recipientCount()]);
    }

    public function update(Request $request, Newsletter $newsletter)
    {
        $data = $this->content($request);
        DB::transaction(function () use ($newsletter, $data) {
            $letter = Newsletter::whereKey($newsletter->id)->lockForUpdate()->firstOrFail();
            abort_unless($letter->status === 'draft', 409);
            $letter->update($data);
        });
        return back()->with('success', trans('newsletter.saved'));
    }

    public function test(Request $request)
    {
        $data = $this->content($request);
        $request->validate(['test_email' => ['required', 'email:rfc', 'max:254']]);
        try {
            Mail::to($request->test_email)->send(new NewsletterMail(trans('newsletter.test_prefix') . ' ' . $data['subject'], $data['body']));
        } catch (\Throwable $exception) {
            return response()->json(['message' => trans('newsletter.mail_failed')], 422);
        }
        return response()->json(['message' => trans('newsletter.test_sent')]);
    }

    public function send(Request $request, Newsletter $newsletter, NewsletterSender $sender)
    {
        $request->validate(['confirm' => ['accepted']]);
        $sender->start($newsletter);
        return redirect()->route('admin.newsletter.show', $newsletter)->with('success', trans('newsletter.started'));
    }

    public function show(Newsletter $newsletter)
    {
        return view('admin.newsletter.show', ['letter' => $newsletter, 'progress' => $newsletter->progress()]);
    }

    public function process(Newsletter $newsletter, NewsletterSender $sender)
    {
        abort_unless(in_array($newsletter->status, ['sending', 'completed'], true), 409);
        return response()->json($sender->process($newsletter));
    }

    private function content(Request $request): array
    {
        return $request->validate([
            'subject' => ['required', 'string', 'max:180', 'not_regex:/[\r\n]/'],
            'body' => ['required', 'string', 'max:20000'],
        ]);
    }
}
