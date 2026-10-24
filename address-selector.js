(function(){
    const API='https://psgc.gitlab.io/api/';
    const NCR_CODE='130000000';
    const NCR={code:NCR_CODE,regionCode:NCR_CODE,name:'Metro Manila'};
    const provinceSelect=document.getElementById('addressProvince');
    const citySelect=document.getElementById('addressCity');
    const barangaySelect=document.getElementById('addressBarangay');
    const status=document.getElementById('addressLocationStatus');
    if(!provinceSelect||!citySelect||!barangaySelect||!status)return;

    const cityCache=new Map();
    const barangayCache=new Map();
    let provinces=[];
    let cityRequest=0;
    let barangayRequest=0;
    let provincesPromise=null;

    function setStatus(message,isError=false){
        status.textContent=message;
        status.classList.toggle('err',isError);
    }

    function setOptions(select,items,placeholder,selectedValue=''){
        select.replaceChildren(new Option(placeholder,''));
        items.forEach(item=>select.add(new Option(item.label,item.value)));

        if(selectedValue&&!Array.from(select.options).some(option=>option.value===selectedValue)){
            select.add(new Option(selectedValue,selectedValue));
        }
        select.value=selectedValue;
        select.disabled=items.length===0;
    }

    function normalizedCityName(value){
        return String(value||'').trim().toLowerCase()
            .replace(/^city of\s+/,'')
            .replace(/\s+city$/,'');
    }

    function cityLabel(name){
        return String(name).replace(/^City of\s+(.+)$/,'$1 City');
    }

    async function getJSON(path){
        const response=await fetch(API+path,{headers:{Accept:'application/json'}});
        if(!response.ok)throw new Error(`Location data request failed (${response.status}).`);
        const data=await response.json();
        if(!Array.isArray(data))throw new Error('Location data response is invalid.');
        return data;
    }

    function loadProvinces(){
        if(provincesPromise)return provincesPromise;
        provincesPromise=(async()=>{
        try{
            const list=await getJSON('provinces/');
            provinces=list
                .filter(province=>province.code&&province.regionCode)
                .concat(NCR)
                .sort((a,b)=>a.name.localeCompare(b.name));
            setOptions(provinceSelect,provinces.map(province=>({
                label:province.name,
                value:province.name
            })),'Select a province');
            citySelect.disabled=true;
            barangaySelect.disabled=true;
            setStatus('Choose a province, then a city / municipality and barangay.');
            return true;
        }catch(error){
            setOptions(provinceSelect,[],'Unable to load provinces');
            citySelect.disabled=true;
            barangaySelect.disabled=true;
            setStatus('Unable to load Philippine locations. Check your internet connection and reload this page.',true);
            console.error('Unable to load Philippine provinces.',error);
            provincesPromise=null;
            return false;
        }
        })();
        return provincesPromise;
    }

    async function loadCities(province,selectedCity='',selectedBarangay=''){
        const request=++cityRequest;
        const barangayVersion=++barangayRequest;
        setOptions(citySelect,[],'Loading cities / municipalities...');
        setOptions(barangaySelect,[],'Select a city / municipality first');
        if(!province)return;
        setStatus('Loading cities / municipalities...');

        try{
            const regionCode=province.regionCode;
            if(!cityCache.has(regionCode)){
                cityCache.set(regionCode,getJSON(`regions/${regionCode}/cities-municipalities/`));
            }
            const regionCities=await cityCache.get(regionCode);
            if(request!==cityRequest)return;
            const matches=regionCities.filter(city=>province.code===NCR_CODE
                ?!city.provinceCode
                :String(city.provinceCode)===String(province.code));
            const options=matches
                .sort((a,b)=>a.name.localeCompare(b.name))
                .map(city=>({label:cityLabel(city.name),value:cityLabel(city.name),record:city}));
            setOptions(citySelect,options,'Select a city / municipality');
            citySelect._locationRecords=options;
            if(selectedCity){
                const match=options.find(option=>normalizedCityName(option.label)===normalizedCityName(selectedCity));
                citySelect.value=match?match.value:selectedCity;
                if(match)await loadBarangays(match.record,selectedBarangay);
                else setOptions(barangaySelect,[],'Select a city / municipality first',selectedBarangay);
            }
            if(request===cityRequest&&barangayVersion===barangayRequest){
                setStatus('Choose a city / municipality, then select a barangay.');
            }
        }catch(error){
            cityCache.delete(province.regionCode);
            if(request!==cityRequest)return;
            setOptions(citySelect,[],'Unable to load cities / municipalities');
            setOptions(barangaySelect,[],'Select a city / municipality first');
            setStatus('Unable to load cities / municipalities. Please try again.',true);
            console.error('Unable to load Philippine cities and municipalities.',error);
        }
    }

    async function loadBarangays(city,selectedBarangay=''){
        const request=++barangayRequest;
        setOptions(barangaySelect,[],'Loading barangays...');
        if(!city)return;
        setStatus('Loading barangays...');

        try{
            if(!barangayCache.has(city.code)){
                barangayCache.set(city.code,getJSON(`cities-municipalities/${city.code}/barangays/`));
            }
            const records=await barangayCache.get(city.code);
            if(request!==barangayRequest)return;
            const options=records
                .sort((a,b)=>a.name.localeCompare(b.name))
                .map(barangay=>({label:barangay.name,value:barangay.name}));
            setOptions(barangaySelect,options,'Select a barangay',selectedBarangay);
            setStatus('Select the barangay for this city / municipality.');
        }catch(error){
            barangayCache.delete(city.code);
            if(request!==barangayRequest)return;
            setOptions(barangaySelect,[],'Unable to load barangays');
            setStatus('Unable to load barangays. Please try again.',true);
            console.error('Unable to load Philippine barangays.',error);
        }
    }

    provinceSelect.addEventListener('change',()=>{
        const province=provinces.find(item=>item.name===provinceSelect.value);
        loadCities(province);
    });

    citySelect.addEventListener('change',()=>{
        const city=(citySelect._locationRecords||[]).find(item=>item.value===citySelect.value);
        loadBarangays(city&&city.record);
    });

    window.setDeliveryAddressLocation=async address=>{
        if(!await loadProvinces())return;
        const province=provinces.find(item=>item.name.toLowerCase()===String(address.province||'').trim().toLowerCase())
            ||(String(address.province||'').toLowerCase().includes('metro manila')||String(address.province||'').toLowerCase()==='ncr'?NCR:null);
        if(!province){
            setStatus('The saved province is not in the current list. Select a province to update this address.',true);
            return;
        }
        provinceSelect.value=province.name;
        await loadCities(province,address.city,address.barangay);
    };

    window.clearDeliveryAddressLocation=()=>{
        provinceSelect.value='';
        citySelect._locationRecords=[];
        setOptions(citySelect,[],'Select a province first');
        setOptions(barangaySelect,[],'Select a city / municipality first');
        setStatus('Choose a province, then a city / municipality and barangay.');
    };

    loadProvinces();
})();
