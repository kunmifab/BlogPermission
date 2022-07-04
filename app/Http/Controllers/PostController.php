<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Tag;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response

     */
    public function __construct()
    {
            $this->middleware('auth.post.user')->only(['create', 'store']);
            $this->middleware('auth.edit.user')->only(['edit', 'update']);
            $this->middleware('auth.admin.user')->only('destroy');
    }

    public function index()
    {
        //
        $posts = Post::with('category', 'author')->get();
        return view('post.index', ['posts' => $posts]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $categories = Category::all();
        $tags = Tag::all();
        return view('post.create', ['categories' => $categories], ['tags' => $tags]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //


        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string'

        ]);

        $category = Category::where('slug', $request->category_slug)->firstOrFail();
        $tags = Tag::whereIn('id', $request->tags)->get();
        $auth_user = auth()->user();

        $post = new Post();
        if($request->image != null){
            $image_path = $request->file('image')->storePublicly('posts');
            move_uploaded_file($image_path, public_path('posts'));
            $post->image_path = $image_path;
        }
        $post->title = Str::title($request->title);
        $post->slug = Str::slug($request->title);
        $post->body = $request->body;
        $post->category()->associate($category);
        $post->author()->associate($auth_user);

        $post->save();
        $post->tags()->sync($tags);

        return redirect()->route('post.index');


    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        //

        return view('post.show', ['post' => $post]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        //
        $post = Post::findOrfail($post->id);
        $categories = Category::all();
        $tags = Tag::all();


        return view('post.edit', ['post' => $post,'categories' => $categories, 'tags' => $tags]);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post)
    {
        //
        $request -> validate([
            'title' => 'string|required|max:255',
            'body' => 'string|required'
        ]);

        $category = Category::where('slug', $request->category_slug)->firstOrFail();
        $tags = Tag::whereIn('id', $request->tags)->get();

        $post = Post::findOrFail($post->id);
        if($request->image != null){
            if($post->image_path != null){
                Storage::delete($post->image_path);
            }
            $image_path = $request->file('image')->storePublicly('posts');

            $post->image_path = $image_path;
        }
        $post->title = Str::title($request->title);
        $post->slug = Str::slug($request->title);
        $post->body = $request->body;
        $post->category()->associate($category);
        $post->tags()->sync($tags);
        $post->save();

        return redirect()->route('post.show', ['post' => $post->id]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        //
        $post = Post::findOrFail($post->id);
        $post->delete();

        return redirect()->route('post.index');
    }
}
