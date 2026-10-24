(function(){
    const selectors=[
        '.cat-tabs',
        '.subcats',
        '.ab-seg',
        '.ad-nav:not(.ad-logout)',
    ];
    const query=selectors.join(',');

    function alignItem(target,item,behavior='smooth'){
        if(!item||target.scrollWidth<=target.clientWidth)return;
        const previous=item.previousElementSibling;
        const alignedItem=target.matches('.ad-nav:not(.ad-logout)')&&previous&&previous.matches('button,a,[role="tab"]')
            ?previous
            :item;
        const containerRect=target.getBoundingClientRect();
        const itemRect=alignedItem.getBoundingClientRect();
        const padding=parseFloat(getComputedStyle(target).paddingLeft)||0;
        const desiredLeft=Math.max(0,target.scrollLeft+itemRect.left-containerRect.left-padding);
        const currentEndSpace=parseFloat(getComputedStyle(target).getPropertyValue('--scroll-nav-end-space'))||0;
        const maxScrollWithoutEndSpace=target.scrollWidth-target.clientWidth-currentEndSpace;
        target.style.setProperty(
            '--scroll-nav-end-space',
            `${Math.max(0,desiredLeft-maxScrollWithoutEndSpace)}px`
        );
        const left=Math.max(0,Math.min(
            target.scrollWidth-target.clientWidth,
            desiredLeft
        ));
        target.scrollTo({left,behavior});
    }

    function enhance(target){
        if(target.dataset.scrollNav==='ready')return;
        target.dataset.scrollNav='ready';

        target.addEventListener('click',event=>{
            const item=event.target.closest('button,a,[role="tab"]');
            if(item&&target.contains(item)){
                requestAnimationFrame(()=>alignItem(target,item));
            }
        });

        const active=target.querySelector('[aria-selected="true"],.active,[aria-current="page"]');
        if(active)requestAnimationFrame(()=>alignItem(target,active,'auto'));
    }

    function scan(){
        document.querySelectorAll(query).forEach(enhance);
    }

    scan();
    new MutationObserver(scan).observe(document.body,{childList:true,subtree:true});
})();
