<?php
namespace App\Controller;

use App\Entity\Enquiry;
use App\Entity\Movie;
use App\Entity\Sale;
use App\Event\SaleEvent;
use App\Form\EnquiryType;
use App\Form\SaleType;
use App\Logger\SaleLogger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MovieController extends Controller
{
    /**
     * @Route("/movie/{movieId}", name="movie")
     */
    public function movie(int $movieId)
    {
        $em = $this->getDoctrine()->getManager();

        return $this->render('movies/movie.html.twig', [
            'movie' => $em->getRepository(Movie::class)->findOneWithActors($movieId),
        ]);
    }

    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        $movies = $this->getDoctrine()
            ->getRepository(Movie::class)
            ->findAll();

        return $this->render('movies/index.html.twig', [
            'movies' => $movies,
        ]);
    }

    /**
     * @Route("/movie/{movieId}/book", name="book")
     */
    public function newSale(SaleLogger $saleLogger, Request $request, $movieId)
    {
        $sale = new Sale();
        $movie = $this->getDoctrine()
            ->getRepository(Movie::class)
            ->find($movieId);

        $form = $this->createForm(SaleType::class, $sale);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($sale);
            $numTickets = $form['numTickets']->getData();
            $this->get('event_dispatcher')
                ->dispatch(
                    SaleEvent::NAME,
                    new SaleEvent($sale, $movie, $numTickets)
                );
            $em->flush();

            $saleLogger->log($sale);

            $this->addFlash(
                'notice',
                'Thank you for your sale!'
            );

            return $this->redirectToRoute('index');
        }

        return $this->render('movies/newSale.html.twig', [
            'form' => $form->createView(),
            'movie' => $movie,
        ]);
    }

/**
 * @Route("/enquiry", name="enquiry")
 */
    public function newEnquiry(Request $request)
    {
        $enquiry = new Enquiry();

        $form = $this->createForm(EnquiryType::class, $enquiry);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($enquiry);
            $em->flush();

            $this->addFlash(
                'notice',
                'Your enquiry has been sent. We will get back to you soon.'
            );

            return $this->redirectToRoute('index');
        }

        return $this->render('newEnquiry.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
