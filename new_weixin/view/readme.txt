һ���µ�ҳ�ж����ڽ��룬 ����ͨ����Ŀҳ����ַ�б�ҳ��ʱ��ؼ�ҳ����롣�������ҳ���ڻ�����תʱ��Ҫͨ��URL���ݲ�������������£�
 
1. ��Ŀҳ���µ�ҳ����Ҫ����
   category_id, price_read, city_id. �����������ǻ�ȡ�µ�ҳjson����Ҫ�ġ�
   mark (����������ҳ��ȡ�ģ�����о�Ҫ��)

2. �µ�ҳ����ַ�б�ҳ����Ҫ����
   
   category_id (�û����޸���ѡƷ��)�� address_id (�����Ĭ�ϵ�ַ�Ļ����ڵ�ַ�б�ҳ�Ṵѡ��

3. ��ַ�б�ҳ���µ�ҳ��
    category_id, price_read, city_id ,(������������֮ǰ�����µ�ҳʱ��ȡjson����Ϊ����������ˣ����ұ���¼)
      address_id, select_address(��ʽ����default_address,���е�address_id���ظ�)

4. �µ�ҳ��ʱ��ؼ�ҳ�� �����������ͨ���ж�url�Ƿ����select_address����
    a. ��ַ��ʹ��default_address ʱ��
       category_id, city_id, price_read, (����������Ҫ�ش�)
       area�� area_id 
    b. ��ַ��ʹ�õ����޸Ĺ��ĵ�ַ��
       category_id, city_id, price_read, area, area_id,
       address_id, select_address (����������Ҫ�ش�)
5. ʱ��ؼ�ҳ���µ�ҳ�������������
    a. category_id, city_id, price_read .������������Ҫ�ش����Ѷ�ȡ�µ�ҳ��json��
       washing_date, washing_time, time_range (���������������������µ��ύʱʹ��)
    b. �������category_id, city_id, price_read �⣬����address_id��select_address�Ļ�ҲҪ�ش�����������ַ����  washing_date, washing_time, time_rang (���������������������µ��ύʱʹ��)
       
     
���� ����д��¥��ϴ���µ�ҳ��������������Ŀҳ��д��¥����ҳ��ʱ��ؼ�ҳ
     1. ��д��¥����ҳ����ת���µ�ҳʱ��ͨ��url��office_building_id=9&office_name=��&office_area=�������� ����office_nameֱ����ʾ��д��¥��ַ���С�
���� ��Ŀҳ��Ϊapp��ios��android�����л�����΢�����л���
     ͨ��UA���Ƿ������edaixi_app�����ж��Ƿ�app����

     1. ΢�Ż����� ��Ŀҳ��php���������ݡ�
     2. app������ ֱ����ruby���������ݡ� ��������ʱ�ȵ���native��handle��ȡ��������Щ������������ruby�ӿڵ�head���֣�������ʱ���͸�ruby�ˡ�

                  �ڵ����ԤԼȡ����ʱ�ǵ���loginHandle �����¼ҳ��ԭ�������ǵ���shoppingHandle �����µ�ҳ��ԭ����������ͨ��sessionId����������:open_session_id�����жϡ�
                  sessionId�������⣺ ruby����{ret��false; error_code: 40001 }, ��ʱ��Ҫ����app�ķ������»��sessionid��ˢ��ҳ�档

