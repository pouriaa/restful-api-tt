<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Comic;
use App\Http\Controllers\Controller;
use \File;
use App\Helpers\AppHelper;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ComicController extends Controller {

	protected $comic;

	/**
	 * Constructor for Comic 
	 *
	 */
	public function __construct(Comic $comic)
	{
		$this->comic = $comic;
		// Ensure user is logged in for pages that require auth
		$this->middleware('auth', ['except' => ['index', 'show', 'archive', 'popular', 'legacy']]);

	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		// Return the latest comic
		$latestComic = $this->comic->orderBy('created_at', 'DESC')->first();
		return redirect('comic/' . $latestComic->seo);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return view('comics.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Requests\StoreComicRequest $request)
	{
		/*Save Comic Info*/
		$comic = new Comic(array(
			'title' 	=> $request->get('title'),
			'seo'		=> Controller::seoUrl($request->get('title')),
			'comment'  	=> $request->get('comment'),
			'is_demon'	=> !is_null($request->get('is_demon')),
			'hits'  	=> 0,
		));
		
		if(File::exists(base_path() . '/public/images/comics/' . Controller::seoUrl($comic->title) . '.png')) {
			return 'title already exists.';
		}
		$comic->save();

		/* Save Comic Image */
		$comicPicName = Controller::seoUrl($comic->title) . '.' . 
		$request->file('comic')->getClientOriginalExtension();
		$request->file('comic')->move(
			base_path() . '/public/images/comics/', $comicPicName
		);

		/* Save Thumbnail Image */
		$thumbPicName = Controller::seoUrl($comic->title) . '-thumbnail.' . 
		$request->file('thumbnail')->getClientOriginalExtension();
		$request->file('thumbnail')->move(
			base_path() . '/public/images/thumbnails/', $thumbPicName
		);

		return redirect('comic');
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($slug)
	{
		// Return comic if it exists in the DB. Else
		// take user to comic home
		if( $comic = $this->comic->whereSeo($slug)->first() )
		{
			$this->addHit($comic->id);
			$comic->seo = Controller::seoUrl($comic->title);
			$comic->latestComicId = $this->comic->orderBy('created_at', 'DESC')->first()->id;
			return view('comics.show')->withComic($comic);
		}
		return redirect('comic');
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		$comic = $this->comic->find($id);
		if ( !is_null($comic) )
			return view('comics.update')->withComic($comic);

		return redirect('comic');   
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update(Requests\StoreComicRequest $request, $id)
	{
		$comic = $this->comic->find($id);

		$seoComicTitle = Controller::seoUrl($comic->title);

		// Delete the pictures associated with
		// the old comic and thumbnail
		
		File::delete(base_path() . '/public/images/comics/' . Controller::seoUrl($comic->title) . '.png');
		File::delete(base_path() . '/public/images/thumbnails/' . Controller::seoUrl($comic->title) . '-thumbnail.jpg');

		/*Save Comic Info*/
		$comic->title 		= $request->get('title');
		$comic->seo 		= Controller::seoUrl($request->get('title'));
		$comic->comment 	= $request->get('comment');
		$comic->is_demon 	= !is_null($request->get('is_demon'));

		$comic->save();

		/* Save Comic Image */
		$comicPicName = Controller::seoUrl($comic->title) . '.' . 
		$request->file('comic')->getClientOriginalExtension();
		$request->file('comic')->move(
			base_path() . '/public/images/comics/', $comicPicName
		);

		/* Save Thumbnail Image */
		$thumbPicName = Controller::seoUrl($comic->title) . '-thumbnail.' . 
		$request->file('thumbnail')->getClientOriginalExtension();
		$request->file('thumbnail')->move(
			base_path() . '/public/images/thumbnails/', $thumbPicName
		);

		return redirect('comic');
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
		$comic = $this->comic->find($id);
		File::delete(base_path() . '/public/images/comics/' . Controller::seoUrl($comic->title) . '.png');
		File::delete(base_path() . '/public/images/thumbnails/' . Controller::seoUrl($comic->title) . '-thumbnail.jpg');
		$comic->delete();

		return 'deleted';
	}

	/**
	 * Increment the hit column for the comic
	 *
	 * @param  int  $id
	 * @return Response
	 */
	private function addHit($id)
	{
		$this->comic->find($id)->increment('hits');
	}
	
	/**
	 * Load the archive view
	 *
	 * @param  int  $season
	 * @return view
	 */
	public function archive($season = null)
	{
		// If no season is selected then
		// go to latest season
		$latestSeason = $this->currentSeason();
		$season = ($season === null) ? $this->currentSeason() : $season;
		$skip = 48 * ($season-1);
		$this->comics = $this->comic
							->orderBy('created_at', 'asc')
							->skip($skip)->take(48)
							->get();
		// Give each comic it's SEO title property
		foreach ($this->comics as $comic) {
			$comic->seo = Controller::seoUrl($comic->title);
		}
		$this->comics->currentSeason	= $this->currentSeason();
		$this->comics->season 			= $season;
		return view('archive')->withComics($this->comics);
	}

	/**
	 * Load the popular view
	 *
	 * @param
	 * @return view
	 */
	public function popular($months = 3)
	{
		$today = Carbon::now();
   		$this->comics = $this->comic
				   			->where('created_at', '>', $today->modify('-'. $months .' months'))
				   			->orderBy('hits', 'DESC')
				   			->take(12)
				   			->get();
		$this->comics->months = $months;

   		// Give each comic it's SEO title property
		foreach ($this->comics as $comic) {
			$comic->seo = Controller::seoUrl($comic->title);
		}
		
		return view('popular')->withComics($this->comics);
	}

	public function currentSeason()
	{
		// Get total number of comics in DB
		$numComs = $this->comic->all()->count();

		return ceil($numComs/48);
	}
	public function legacy($id) {
		return redirect('/comic/'.$this->comic->find($id)->seo);
	}

}
