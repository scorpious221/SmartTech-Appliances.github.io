(function(){
    if(document.querySelector('.st-back-to-top'))return;

    const button=document.createElement('button');
    button.className='st-back-to-top';
    button.type='button';
    button.setAttribute('aria-label','Back to top');
    button.title='Back to top';
    button.innerHTML='<i class="bi bi-chevron-double-up" aria-hidden="true"></i>';
    document.body.appendChild(button);

    const updateVisibility=()=>{
        button.classList.toggle('is-visible',window.scrollY>400);
    };

    window.addEventListener('scroll',updateVisibility,{passive:true});
    updateVisibility();

    button.addEventListener('click',()=>{
        window.scrollTo({
            top:0,
            behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches
                ?'auto'
                :'smooth'
        });
    });
})();
