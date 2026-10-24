(function(){
    class WishlistRequestError extends Error{
        constructor(message,status){
            super(message);
            this.name='WishlistRequestError';
            this.status=status;
        }
    }

    async function request(method,body){
        const response=await fetch('php/wishlist.php',{
            method,
            credentials:'same-origin',
            headers:{
                Accept:'application/json',
                ...(body?{'Content-Type':'application/json'}:{})
            },
            ...(body?{body:JSON.stringify(body)}:{})
        });

        let result;
        try{
            result=await response.json();
        }catch(error){
            throw new WishlistRequestError('The wishlist service returned an invalid response.',response.status);
        }

        if(!response.ok){
            throw new WishlistRequestError(result.message||'Unable to update your wishlist.',response.status);
        }

        return result;
    }

    function login(){
        const page=location.pathname.split('/').pop()||'index.html';
        location.assign('login.php?next='+encodeURIComponent(page));
    }

    window.StWishlist={
        async load(){
            const result=await request('GET');
            return Array.isArray(result.items)?result.items.map(String):[];
        },
        async loadProducts(){
            const result=await request('GET');
            return Array.isArray(result.products)?result.products:[];
        },
        async set(productCode,wishlisted){
            try{
                const result=await request('POST',{
                    action:wishlisted?'add':'remove',
                    product_code:String(productCode)
                });
                return Array.isArray(result.items)?result.items.map(String):[];
            }catch(error){
                if(error.status===401)login();
                throw error;
            }
        },
        isUnauthorized(error){
            return error instanceof WishlistRequestError&&error.status===401;
        }
    };
})();
