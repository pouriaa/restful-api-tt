<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Blog;
use App\Http\Controllers\Controller;
use App\Comic;
use Illuminate\Http\Request;

class BlogController extends Controller {

	protected $blog;

	
	/**
	 * Constructor for Blog
	 *
	 */
	public function __construct(Blog $blog)
	{
		$this->blog = $blog;

		$this->middleware('auth', ['except' => ['index', 'importBlog']]);
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$blogs = $this->blog->orderBy('created_at', 'desc')->get();
		
		$comics = Comic::orderByRaw("RAND()")->take(4)->get();
		return view('blogs.index')->withData([$blogs, $comics]);

	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return view('blogs.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Requests\StoreBlogPostRequest $request)
	{
		$input = $request->all();

		$this->blog->fill($input);

		$this->blog->save();

		return redirect('blog');
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		// Return blog if it exists in the DB. Else
		// take user to blog index
		if( $blog = $this->blog->find($id) )
			return $blog;

		return redirect('blog');
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		$blog = $this->blog->find($id);
		if ( !is_null($blog) )
			return view('blogs.update')->withBlog($blog);

		return redirect('blog');
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update(Requests\StoreBlogPostRequest $request, $id)
	{
		$input = $request->all();

		$this->blog = $this->blog->find($id);

		$this->blog->fill($input);

		$this->blog->save();

		return redirect('blog'); 
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		// Search and destroy
		$blog = $this->blog->find($id);
		$blog->delete();

		return 'blog deleted';
	}
}
